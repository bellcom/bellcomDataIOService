<?php

namespace Bellcom;
use Pimple;
use Exception;
use Db;

/**
 * undocumented class
 *
 * @packaged default
 * @author Henrik Farre <hf@bellcom.dk>
 **/
class processorCleanMissingProducts
{
  const ACTION_DEACTIVATE  = 10;
  const ACTION_MOVE_TO_CAT = 20;
  const ACTION_RESET_UPDATE_MARK = 30;
  const ABORT_ON_ERROR = 'abort_on_error';

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
    if ( isset($this->app['processConfig']['params']) && in_array(self::ABORT_ON_ERROR, $this->app['processConfig']['params']) && $this->app['error_counter'] > 0 )
    {
      $this->app['log']->msg( 'Aborting process because there has been a previous error', log::WARNING );
      return false;
    }

    foreach( $this->app['processConfig']['actions'] as $action )
    {
      switch ($action) 
      {
        case self::ACTION_DEACTIVATE:
          $products = $this->getNotUpdatedActiveProducts();
          $this->deactivateProducts($products);
          break;
        case self::ACTION_MOVE_TO_CAT:
          // TODO
          break;
        case self::ACTION_RESET_UPDATE_MARK:
          Db::getInstance()->Execute("UPDATE `"._DB_PREFIX_."product` SET updated = 0");
          break;
      }
    }
  }

  /**
   * deactivateProducts
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  protected function deactivateProducts( $products )
  {
    $ids = array();

    foreach ( $products as $product )
    {
      $ids[] = $product['id_product'];
    }

    if ( !empty($ids) )
    {
      $sql =  "UPDATE `"._DB_PREFIX_."product` SET active = 0 WHERE id_product IN (".implode(",",$ids).")";
      //echo $sql."\n";
      return Db::getInstance()->Execute($sql);
    }

    return true;
  }

  /**
   * getNotUpdatedActiveProducts
   * @return array Contaning prestashop product ids
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  protected function getNotUpdatedActiveProducts()
  {
    static $cache;
    if ( empty($cache) )
    {
      $cache = Db::getInstance()->ExecuteS("SELECT id_product FROM `"._DB_PREFIX_."product` WHERE updated = 0 AND active = 1");
    }

    return $cache;
  }
}
