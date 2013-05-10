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
class processorEncoding
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
    if ( !isset($this->app['processConfig']['files']) || empty($this->app['processConfig']['files']))
    {
      echo "[ ERROR ] Missing files list in processorEncoding\n";
      return false;
    }

    foreach( $this->app['processConfig']['files'] as $file )
    {
      if (!is_file( $file['src'] ))
      {
        throw new Exception( 'File "'. $file['src'] .'" does not exist' );
      }

      $cmd = sprintf('iconv -f %s -t %s "%s" -o "%s"', $file['encoding_from'], $file['encoding_to'], $file['src'], $file['dst'] );

      $output = system($cmd, $retval);

      if ( $retval != 0 )
      {
        throw new Exception( 'Iconv failed, output was: "'.$output.'"' );
      }
    }
  }
}
