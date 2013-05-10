<?php

namespace Bellcom;
use Pimple;
use Exception;

/**
 * undocumented class
 *
 * @packaged default
 * @author Henrik Farre <hf@bellcom.dk>
 **/
class ftpTransport
{
  private $app = null;
  private $connections = array();

  /**
   * undocumented function
   *
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public function __construct(Pimple $app)
  {
    $this->app = $app;
  }

  /**
   * undocumented function
   *
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public function __destruct()
  {
    foreach ($this->connections as $con)
    {
      ftp_close($con);
    }
  }

  /**
   * undocumented function
   *
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public function fetch()
  {
    $this->app['log']->msg( 'Starting fetch', log::DEBUG );
    foreach ($this->app['transportConfig'] as $jobName => $config) 
    {
      $this->app['log']->msg( 'Connection to host "'. $config['host'] .'"', log::DEBUG );
      $con = ftp_connect( $config['host'] );

      if ( $con === false )
      {
        throw new Exception('Could not connect to ftp server "'.$config['host'].'"');
      }
      
      $this->connections[$jobName] = $con;

      $loginResult = ftp_login($this->connections[$jobName],$config['username'],$config['password']);

      if ( $loginResult === false )
      {
        throw new Exception('Could not login to ftp server "'.$config['host'].'"');
      }

      foreach ( $config['files'] as $file )
      {
        if ( !isset($file['override']) || !$file['override'] )
        {
          if ( is_file($file['dst']) )
          {
            $this->app['log']->msg( 'File "'.$file['dst'].'" exists, and override not specified', log::ERROR );
            continue;
          }
        }

        $this->app['log']->msg( 'Fetching file "'. $file['src'] .'"', log::DEBUG );
        if ( !ftp_get($this->connections[$jobName],$file['dst'],$file['src'], ( isset($file['mode']) ? $file['mode'] : FTP_ASCII ) ) )
        {
          $this->app['log']->msg( 'Could not fetch file "'.$file['src'].'"', log::ERROR );
        }
      }
    }
  }
} // END class ftpTransport
