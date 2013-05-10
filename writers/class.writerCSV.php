<?php

namespace Bellcom;
use Pimple;
use Exception;

/**
 * undocumented class
 *
 * @packaged bellcom
 * @author Henrik Farre <hf@bellcom.dk>
 **/
class writerCSV extends writer 
{
  private $app = null;
  private $fileHandle = null;
  private $delimiter = ',';
  private $enclosure = '"';
  private $encoding = 'UTF-8';

  public function __construct(Pimple $app)
  {
    $this->app = $app;
    $this->delimiter = (isset($app['writerConfig']['params']['delimiter'])) ? $app['writerConfig']['params']['delimiter'] : ',';
    $this->enclosure = (isset($app['writerConfig']['params']['enclosure'])) ? $app['writerConfig']['params']['enclosure'] : '"';
    $this->eol       = (isset($app['writerConfig']['params']['eol'])) ? $app['writerConfig']['params']['eol'] : PHP_EOL;
    $this->encoding  = (isset($app['writerConfig']['params']['encoding'])) ? $app['writerConfig']['params']['encoding'] : 'UTF-8';
  }

  public function open($file)
  {
    if ( is_file($file) )
    {
      throw new Exception('File "'.$file.'" exists');
    }
    $this->app['log']->msg( 'Writing file "'.$file.'"', log::DEBUG );
    $this->fileHandle = fopen( $file, 'w' );
  }

  public function __destruct()
  {
    $this->close();
  }

  public function close()
  {
    if (is_resource($this->fileHandle))
    {
      fclose($this->fileHandle);
    }
  }

  /**
   * write
   * @return bool
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public function write(array $data)
  {
    if ( $this->encoding != 'UTF-8' ) // Only supports UTF-8 and ISO-8859-1
    {
      array_walk( $data, function(&$item,$key) { $item = iconv("UTF-8", "ISO-8859-1", $item); } );
    }

    if ( $this->eol != PHP_EOL ) // Handle writing DOS EOL
    {
      $sepString = $this->enclosure . $this->delimiter . $this->enclosure;
      $line = $this->enclosure . implode( $sepString, $data) . $this->enclosure . $this->eol;
      fwrite($this->fileHandle,$line);
    }
    else
    {
      fputcsv($this->fileHandle,$data, $this->delimiter, $this->enclosure);
    }
  }
}
