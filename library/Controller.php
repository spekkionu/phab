<?php

abstract class Controller {

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

  public function init(){
    if($this->cli->options['quiet']){
      $this->debug = false;
    }
  }

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
  }

  /**
   * Sends message to user
   * Does not display anything if quite option was set
   * @param string $message The message to display
   * @param boolean $color Show message with ANSI colors
   * @param boolean $force Show message even if quiet option was set
   * @return void
   */
  protected function showMessage($message="", $color = false, $force = false){
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
  protected function dump($data, $label=null){
    $this->showMessage($label);
    var_dump($data);
  }

  /**
   * Shows error message
   * @param type $message
   */
  protected function throwError($message){
    if($this->config['show_colors']){
      $message = Console_Color::convert("%W%1".Console_Color::escape($message)."%n");
    }
    echo $message.PHP_EOL;
  }

}