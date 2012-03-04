<?php
define("SYSTEM", dirname(__FILE__));
define("LIBRARY", SYSTEM.DIRECTORY_SEPARATOR.'library');

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

require_once(LIBRARY.'/ProjectCreator.php');
$project = new ProjectCreator($result, $config);
$project->createAction();

// Complete appliction
exit(PHP_EOL);