<?php
define("SYSTEM", dirname(__FILE__));
define("LIBRARY", SYSTEM.DIRECTORY_SEPARATOR.'library');

// Load the CLI parser
require_once('Console/CommandLine.php');
require_once('Console/Color.php');
$parser = Console_CommandLine::fromXmlFile(SYSTEM.'/config/commands.xml');
try {
    $result = $parser->parse();
} catch (Exception $e) {
    $parser->displayError($e->getMessage());
    exit;
}

if($result->options['config']){
  // A config file was included
  if(!is_file($result->options['config'])){
    $parser->displayError("Requested config not found.");
  }
  $config = realpath($result->options['config']);
}elseif(is_file(getcwd().DIRECTORY_SEPARATOR.'phab.yml')){
  $config = getcwd().DIRECTORY_SEPARATOR.'phab.yml';
}elseif(is_file($_SERVER['HOME'].DIRECTORY_SEPARATOR.'phab.yml')){
  // Use the config in the user's home directory
  $config = $_SERVER['HOME'].DIRECTORY_SEPARATOR.'phab.yml';
}else{
  // Use the default config in the application directory
  $config = SYSTEM.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'phab.yml';
}

echo "Using config file {$config}".PHP_EOL;

try{
  // Load Config
  require_once(LIBRARY.'/sfYaml/lib/sfYaml.php');
  $config = sfYaml::load($config);
}catch(Exception $e){
  $parser->displayError("Error parsing config file - {$e->getMessage()}");
}


// Load default config to merge with
$default = sfYaml::load(SYSTEM.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'default.yml');
if(is_array($config)){
  $config = array_merge($default, $config);
}else{
  $config = $default;
}

// Turn off Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 0);

// Set Timezone
date_default_timezone_set($config['date_timezone']);



require_once(LIBRARY.'/ProjectCreator.php');
$project = new ProjectCreator($result, $config);
$project->createAction();

// Complete appliction
exit(PHP_EOL);