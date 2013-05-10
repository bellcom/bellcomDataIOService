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
class exporter
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

  public function run()
  {
    $data = array();
    $factory = factory::getInstance( $this->app );

    // FIXME: hardcoded path
    $pathArray = explode('/',getcwd());
    $basePath = '/var/www/'. $pathArray[3] .'/c5/export';

    foreach ( $this->app['exportConfig'] as $jobs => $config )
    {
      $this->app['reader'] = $factory->build( $config['reader'], $config );
      $this->app['writer'] = $factory->build( $config['writer'], $config );

      // FIXME: hardcoded method, should be more generic
      $orders = $this->app['reader']->getOrdersWithExternalStatus();

      foreach ($orders as $order) 
      {
        try
        {
          $this->app[ 'reader' ]->open( $order['id_order'] );
          // FIXME: hardcoded path
          $this->app['writer']->open( $basePath.'/order_'.$order['id_order'].'.csv' );

          while ( $data = $this->app[ 'reader' ]->getData() )
          {
            $this->app['writer']->write( $data );
          }

          $this->app['reader']->close();
          $this->app['writer']->close();
        }
        catch (Exception $e)
        {
          // FIXME: hardcoded class name and method, should be more generic
          $this->app['reader']->setOrderExternalStatus( $order['id_order'], customC5OrderDumper::STATUS_FAILED );
          $this->app['log']->msg( $e->getMessage(), log::ERROR);
        }
      }
    }
  }
}
