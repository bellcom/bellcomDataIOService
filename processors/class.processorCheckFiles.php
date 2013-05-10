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
class processorCheckFiles
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
      if ( !is_file($file['src']) )
      {
        throw new Exception( 'File "'.$file['src'].'" not found, skipping' );
      }
    }
  }
}
