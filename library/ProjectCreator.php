<?php

class ProjectCreator {

  /**
   * Console_CommandLine_Result $cli
   */
  protected $cli;

  /**
   * Path of the current directory
   * @var string $path
   */
  protected $path;

  /**
   * If true will display status messages to user
   * Will be false if --quiet param is set
   * @var boolean $debug
   */
  protected $debug = true;

  /**
   * Config object
   * @var array $config
   */
  protected $config;

  /**
   * Class Contructor
   * @param Console_CommandLine_Result $result The parsed command line results
   */
  public function __construct(Console_CommandLine_Result $result, array $config){
    // Save config object
    $this->config = $config;
    // Save current path
    $this->path = getcwd();
    // Save parsed cli arguments
    $this->cli = $result;
    if($this->cli->options['quiet']){
      $this->debug = false;
    }
  }

  public function createAction(){
    $this->showMessage("Create a new project");
    $path = $this->cli->args['path'];
    if(is_dir($path)){
      $this->showMessage("%rProject directory already exists.%n", true);
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
        return $this->throwError("Project directory is not empty.");
      }
    }
    $this->showMessage("Checkout project files from repository.");
    if($this->config['repository']['type'] == 'svn'){
      $this->showMessage("Export svn repository {$this->config['repository']['url']}.");
      // Checkout Repository
      require_once 'PEAR/ErrorStack.php';
      require_once 'VersionControl/SVN.php';
      $svnstack = PEAR_ErrorStack::singleton('VersionControl_SVN');
      $options = array('fetchmode' => VERSIONCONTROL_SVN_FETCHMODE_RAW, 'svn_path'=>$this->config['subversion']['svn_path']);
      $svn = VersionControl_SVN::factory('export', $options);
      $switches = array();
      if( $this->config['repository']['params']['username']){
        $switches['username'] = $this->config['repository']['params']['username'];
      }
      if($this->config['repository']['params']['password']){
        $switches['password'] = $this->config['repository']['params']['password'];
      }
      if($this->config['repository']['params']['revision']){
        $switches['revision'] = $this->config['repository']['params']['revision'];
      }
      $switches['force'] = true;
      $args = array($this->config['repository']['url'], $path);
      if ($output = $svn->run($args, $switches)) {
          $this->showmessage($output, true);
      } else {
        $this->dump($svnstack->getErrors(), 'SVN Export Errors');
        $this->throwError("Failed to export repository.");
      }
    }elseif($this->config['repository']['type'] == 'git'){
      $command = escapeshellcmd($this->config['git']['git_path'] . " clone " . $this->config['repository']['url'] . " " . $path);
      passthru($command);
      if(!is_dir($path)){
        return $this->throwError("Project path does not exist. Repository checkout likely failed.");
      }
			// Now we can set the project path to absolute
			$path = realpath($path);
			// Change to project dir
			chdir($path);
      if($this->config['repository']['params']['revision']){
        $command = escapeshellcmd($this->config['git']['git_path'] . " checkout " . $this->config['repository']['params']['revision']);
        passthru($command);
      }
			$this->showMessage("Checkout submodules.");
			// Now pull submodules
			$command = escapeshellcmd($this->config['git']['git_path'] . " submodule update --init");
			passthru($command);

    }elseif($this->config['repository']['type'] == 'hg'){
      return $this->throwError("Mercurial checkout not implemented.");
    }
    if(!is_dir($path)){
      return $this->throwError("Project path does not exist. Repository checkout likely failed.");
    }
    // Now we can set the project path to absolute
    $path = realpath($path);
    // Change to project dir
    chdir($path);
    if($this->config['phing']['enabled']){
      $file = ($this->config['phing']['file'])?$this->config['phing']['file']:"build.xml";
      // Run phing build script
      if(!file_exists($file)){
        return $this->throwError("The requested phing build script {$file} does not exist.");
      }
      $file = escapeshellcmd($file);
      if($this->config['phing']['target']){
        $target = $this->config['phing']['target'];
      }else{
        $target = "";
      }
      $this->showMessage("Execute phing build script.");
      $command = escapeshellcmd("phing -f ".$file." -verbose ".$target);
      passthru($command);
    }
    $this->showMessage("%2%kProject has been installed at {$path}%n", true);
  }

  /**
   * Sends message to user
   * Does not display anything if quite option was set
   * @param string $message The message to display
   * @param boolean $color Show message with ANSI colors
   * @param boolean $force Show message even if quiet option was set
   * @return void
   */
  public function showMessage($message="", $color = false, $force = false){
    if($this->debug || $force){
      if($color && $this->config['show_colors']){
        $message = Console_Color::convert($message);
      }
      echo $message.PHP_EOL;
    }
  }

  /**
   * Dumps data with Zend_Debug::dump()
   * @param mixed $data
   * @param string $label
   * @return void
   */
  public function dump($data, $label=null){
    $this->showMessage($label);
    var_dump($data);
  }

  /**
   * Shows error message
   * @param type $message
   */
  public function throwError($message){
    if($this->config['show_colors']){
      $message = Console_Color::convert("%W%1".Console_Color::escape($message)."%n");
    }
    echo $message.PHP_EOL;
  }

}