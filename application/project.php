<?php

class ProjectController extends Controller{

  public function createAction(){
    $this->showMessage("Create a new project");
    $path = $this->cli->command->args['path'];
    if(is_dir($path)){
      $this->showMessage("%rProject directory already exists.%n", true);
      if($this->cli->command->options['force']){
        $this->showMessage("Delete any existing files in the directory.");
      }
      // Now we can set the project path to absolute
      $path = realpath($path);
      // Change to project dir
      chdir($path);
      // Make sure directory is empty
      $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::CHILD_FIRST);
      foreach ($iterator as $fileinfo) {
        $fpath = trim(preg_replace('/^'.preg_quote($path, '/').'/', '', $fileinfo->getPathname(), 1),DIRECTORY_SEPARATOR);
        $split = explode(DIRECTORY_SEPARATOR, $fpath);
        // remove path
        $first = array_shift($split);
        // skip repository directories
        if(substr($first, 0, 1) != '.' && !in_array($first, array('.svn','.hg','.git','.bzr','CVS'))){
          // It is not empty, if the --force option is set remove files in it otherwise throw error
          if($this->cli->command->options['force']){
            $this->showMessage("%rDelete {$fileinfo->getRealPath()}%n", true);
            if ($fileinfo->isDir()) {
             if(!rmdir($fileinfo->getRealPath())){
               return $this->throwError("Could not delete directory {$fileinfo->getRealPath()}.");
             }
            } else {
             if(!unlink($fileinfo->getRealPath())){
               return $this->throwError("Could not delete file {$fileinfo->getRealPath()}.");
             }
            }
          }else{
            return $this->throwError("Project directory is not empty.");
          }
        }
      }
    }
    $this->showMessage("Checkout project files from repository.");
    if($this->config->repository->type == 'svn'){
      $this->showMessage("Export svn repository {$this->config->repository->url}.");
      // Checkout Repository
      require_once 'VersionControl/SVN.php';
      $svnstack = PEAR_ErrorStack::singleton('VersionControl_SVN');
      $options = array('fetchmode' => VERSIONCONTROL_SVN_FETCHMODE_RAW, 'svn_path'=>$this->config->subversion->svn_path);
      $svn = VersionControl_SVN::factory('export', $options);
      $switches = array();
      if( $this->config->repository->params->username &&  $this->config->repository->params->password){
        $switches['username'] = $this->config->repository->params->username;
        $switches['password'] = $this->config->repository->params->password;
      }
      if($this->config->repository->params->revision){
        $switches['revision'] = $this->config->repository->params->revision;
      }
      $switches['force'] = true;
      $args = array($this->config->repository->url, $path);
      if ($output = $svn->run($args, $switches)) {
          $this->showmessage($output, true);
      } else {
        $this->dump($svnstack->getErrors(), 'SVN Export Errors');
        $this->throwError("Failed to export repository.");
      }
    }elseif($this->config->repository->type == 'git'){

    }
    if(!is_dir($path)){
      return $this->throwError("Project path does not exist. Repository checkout likely failed.");
    }
    // Now we can set the project path to absolute
    $path = realpath($path);
    // Change to project dir
    chdir($path);
    if($this->config->phing->enabled){
      $file = ($this->config->phing->file)?$this->config->phing->file:"build.xml";
      // Run phing build script
      if(!file_exists($file)){
        return $this->throwError("The requested phing build script {$file} does not exist.");
      }
      $file = escapeshellcmd($file);
      if($this->config->phing->target){
        $target = escapeshellcmd($this->config->phing->target);
      }else{
        $target = "";
      }
      $this->showMessage("Execute phing build script.");
      $command = "phing -f {$file} -verbose {$target}";
      $this->showMessage($command);
      $output = shell_exec($command);
      $this->showmessage($output, true);
    }
  }
}