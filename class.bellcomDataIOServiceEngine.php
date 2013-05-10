<?php
namespace Bellcom;
require 'bootstrap.php';
use Pimple;
use Exception;

/**
 * undocumented class
 *
 * @packaged default
 * @author Henrik Farre <hf@bellcom.dk>
 **/
class bellcomDataIOServiceEngine
{
  const IMPORT   = 'import';
  const EXPORT   = 'export';
  const FETCH    = 'fetch';
  const PROCESS  = 'process';
  const HOOK     = 'hook';
  const CUSTOM   = 'custom';

  /**
   * Provides a Cron interface to the import/export functions
   *
   * @param string $taskID
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public function cronTask( $taskID = null )
  {
    if ( is_null($taskID) )
    {
      throw new Exception('Missing argument');
    }

    if ( !is_file( 'staticConfigs/'.$taskID ) )
    {
      throw new Exception('Missing config file');
    }

    $tasks = require 'staticConfigs/'.$taskID; // FIXME: should use db as config

    $app = new Pimple();

    // Parts of the import might set this, and other might parts might refuse to run if it is set
    $app['error_counter'] = 0;
    $app['error_list'] = array();

    $app['log'] = $app->share(function($c) {
      return new log(log::TIMING);
    });

    $app['log']->msg("Importing starting on job: ". $tasks['name'], log::INFO);

    $factory = factory::getInstance( $app );

    foreach ($tasks['steps'] as $taskName => $setup) 
    {
      if ( isset( $setup['disabled'] ) && $setup['disabled'] === true )
      {
        continue;
      }
      $app['log']->msg("Starting step: ". $taskName, log::INFO);
      $task = $factory->build( $setup['type'], $setup['config'] );
      $task->run();
    }
  }

  /**
   * getConfigList
   * Returns a list of config settings
   * @return array
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public function getConfigList()
  {
    $configs = array();
    foreach ( glob("staticConfigs/*.php") as $configFile ) 
    {
      $config = include $configFile;
      $config['file'] = basename( $configFile );
      $configs[] = $config;
    }

    return $configs;
  }

  /**
   * startImport
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public function startImport( $config )
  {
    ob_start();
    $this->cronTask($config['config-file']);
    $out = ob_get_contents();
    ob_end_clean();
    return true;
  }

} // END class bellcomDataIOServiceEngine
