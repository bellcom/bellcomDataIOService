<?php

namespace Bellcom;
use Pimple;
use Exception;
use ZipArchive;

/**
 * undocumented class
 *
 * @packaged default
 * @author Henrik Farre <hf@bellcom.dk>
 **/
class processorExtractor
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
      if (!is_file( $file['src'] ))
      {
        throw new Exception( 'File "'. $file['src'] .'" does not exist' );
      }
      if (!is_dir( $file['dst'] ))
      {
        throw new Exception( 'Directory "'. $file['dst'] .'" does not exist' );
      }

      $archiveInfo = pathinfo($file['src']);
      $archiveType = strtolower($archiveInfo['extension']);
      switch ($archiveType) 
      {
        case 'zip':
          $zip = new ZipArchive();
          if ($zip->open( $file['src'] ) === true) 
          {
            $zip->extractTo($file['dst']);
            $zip->close();
          } 
          else 
          {
            throw new Exception( 'Zip extraction of file "'.$file['src'].'" failed' );
          }
          break;
        default:
          throw new Exception( 'Does not know how to handle archive of type "'.$archiveType.'"' );
          break;
      }
    }
  }
}
