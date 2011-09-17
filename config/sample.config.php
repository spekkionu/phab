<?php
$config = array();
// Set to true to show colors, may not work on windows
$config['show_colors'] = true;

// Set Timezone in case not set in php.ini
$config['date_timezone'] = "America/Los_Angeles";

// Does this project have a phing build script?
$config['phing']['enabled'] = false;
// What is the path to the build script relative to the project base dir
// if blank use build.xml
$config['phing']['file'] = NULL;
// What build targets should be run
// leave blank to run default
// separate multiple targets with spaces
$config['phing']['target'] = NULL; 

// Project Repository
// Can be svn, hg, or git
$config['repository']['type'] = "svn";
// the url of the repository
$config['repository']['url'] = ""; 
// credentials
$config['repository']['params']['username'] = ""; 
$config['repository']['params']['password'] = ""; 
// you may request a specific revision
$config['repository']['params']['revision'] = NULL;

// Path to subversion executable, must be absolute path if not on the system path
$config['subversion']['svn_path'] = "svn";
// Path to git executable, must be absolute path if not on the system path
$config['git']['git_path'] = "git";
// Path to mercurial executable, must be absolute path if not on the system path
$config['hg']['hg_path'] = "hg";
    
// Return config variable
return $config;
