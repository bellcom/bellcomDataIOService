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
class processorCleanUp
{
  private $app = null;

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
  public function process()
  {
    foreach( $this->app['processConfig']['files'] as $file )
    {
      switch ($file['action']) 
      {
        case 'move':
          // TODO: error check/handling
          rename($file['src'],$file['dst']);
          break;
        case 'delete':
          break;
      }
    }
  }
}
