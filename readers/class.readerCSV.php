<?php

namespace Bellcom;
use Pimple;
use Exception;

class readerCSV
{
  private $app = null;
  private $fileHandle = null;
  private $delimiter = ',';
  private $enclosure = '"';

  public function __construct(Pimple $app)
  {
    $this->app = $app;
    $this->delimiter = (isset($app['readerConfig']['params']['delimiter'])) ? $app['readerConfig']['params']['delimiter'] : ',';
    $this->enclosure = (isset($app['readerConfig']['params']['enclosure'])) ? $app['readerConfig']['params']['enclosure'] : '"';
  }

  public function open($file)
  {
    if ( !is_file($file) )
    {
      throw new Exception('File "'.$file.'" not found');
    }
    $this->app['log']->msg( 'Reading file "'.$file.'"', log::DEBUG );
    $this->fileHandle = fopen( $file, 'r' );
  }

  public function __destruct()
  {
    $this->close();
  }

  public function close()
  {
    if ( is_resource($this->fileHandle ) )
    {
      fclose($this->fileHandle);
    }
  }

  public function getData()
  {
    return fgetcsv($this->fileHandle, 2048, $this->delimiter, $this->enclosure);
  }
}
