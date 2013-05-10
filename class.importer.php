<?php

namespace Bellcom;
use Pimple;
use Exception;

/**
 * 
 *
 * @packaged bellcom
 * @author Henrik Farre <hf@bellcom.dk>
 **/
class importer
{
  private $app = null;
  private $queue  = array();
  private $maxInQueue = 200;
  const flushQueue = true;
  // FIXME: these might not be productWriter, but prestaShopProductWriter
  /*const PRODUCT_WRITER    = 'productWriter';
  const CATEGORY_WRITER   = 'categoryWriter';
  const CSV_READER = 'readerCSV';*/
  private $currentLine = 1;
  private $skipNumberOfLines = 0;

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

  public function run()
  {
    $data = array();
    $factory = factory::getInstance( $this->app );

    foreach ( $this->app['importConfig'] as $jobs => $config )
    {
      $this->app['reader'] = $factory->build( $config['reader'], $config );

      if ( isset($this->app['readerConfig']['params']['skipNumberOfLines']) && $this->app['readerConfig']['params']['skipNumberOfLines'] > 0 )
      {
        $this->skipNumberOfLines = $this->app['readerConfig']['params']['skipNumberOfLines'];
      }

      foreach( $config['files'] as $file )
      {
        try
        {
          $this->app[ 'reader' ]->open($file);

          while ( $data = $this->app[ 'reader' ]->getData() )
          {
            if ( !( $this->currentLine > $this->skipNumberOfLines ) )
            {
              $this->currentLine++;
              continue;
            }

            if ( isset($this->app['readerConfig']['params']['skipLinesNotMatching']) )
            {
              $field = $this->app['readerConfig']['params']['skipLinesNotMatching']['field'];
              $value = $this->app['readerConfig']['params']['skipLinesNotMatching']['value'];
              if ( $data[ $field ] != $value )
              {
                continue;
              }
            }

            if ( isset($this->app['readerConfig']['params']['skipLinesMatching']) )
            {
              $field = $this->app['readerConfig']['params']['skipLinesMatching']['field'];
              $value = $this->app['readerConfig']['params']['skipLinesMatching']['value'];
              if ( $data[ $field ] == $value )
              {
                continue;
              }
            }
            
            try
            {
              $this->queue[] = $this->app[ 'mapper' ]->map( $data );
            }
            catch ( Exception $e )
            {
              $this->app['error_counter'] = $this->app['error_counter'] + 1;
              // Doing this directly on the pimple container will result in "PHP Notice:  Indirect modification of overloaded element of Pimple..."
              $errorList = $this->app['error_list'];
              $errorList[] = $e->getMessage();
              $this->app['error_list'] = $errorList;
            }

            $this->currentLine++;
            $this->processQueue();
          }

          $this->processQueue( self::flushQueue );

          $this->app['reader']->close();
        }
        catch (Exception $e)
        {
          $this->app['log']->msg( 'The following error was encountered while processing: '. print_r($data,1) ."\n". $e->getMessage(), log::ERROR);
        }
      }
    }
  }

  protected function processQueue( $flushQueue = false )
  {
    if ( !$flushQueue )
    {
      $itemsInQueue = count($this->queue);
      if ( $itemsInQueue < $this->maxInQueue )
      {
        return true;
      }
    }

    while ( !empty($this->queue) )
    {
      $data = array_shift($this->queue);

      if ( $data === false )
      {
        continue;
      }

      try
      {
        $writer = __NAMESPACE__.'\\'.$data['writer'].'::write';
        $status = call_user_func($writer,$data);
      }
      catch (Exception $e)
      {
        echo $e->getMessage()."\n";
      }
    }
  }
}
