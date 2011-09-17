<?php
define("SYSTEM", dirname(__FILE__));
define("LIBRARY", SYSTEM.DIRECTORY_SEPARATOR.'library');
define("APPLICATION", SYSTEM.DIRECTORY_SEPARATOR.'application');

// Add library folder to thye include path
// Make sure it includes Zend Framework and PEAR libraries
set_include_path(
  LIBRARY . PATH_SEPARATOR.
  get_include_path()
);

// Load Config
$config = require(SYSTEM.'/config/config.php');

// Turn off Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 0);

// Set Timezone
date_default_timezone_set($config['date_timezone']);

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
// Init MVC
if($result->command_name){
  $command = explode(':',$result->command_name, 2);
  $command = array(
    'controller' => $command[0],
    'action' => $command[1]
  );
  // Init Controller
  $controller = ucwords($command['controller']).'Controller';
  $path = APPLICATION."/{$command['controller']}.php";
  require_once(LIBRARY.'/Controller.php');
  require($path);
  $controller = new $controller($result, $config);
  $controller->init();
  // Execute Action
  $action = $command['action'].'Action';
  $controller->$action();
}else{
  echo "You must request a valid command.";
}
// Complete appliction
exit(PHP_EOL);