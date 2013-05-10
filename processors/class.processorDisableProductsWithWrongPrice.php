<?php

namespace Bellcom;
use Product;
use Pimple;
use Exception;
use Db;
use Configuration;

/**
 * undocumented class
 *
 * @packaged default
 * @author Henrik Farre <hf@bellcom.dk>
 **/
class processorDisableProductsWithWrongPrice
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
    $sql = "SELECT id_product FROM `"._DB_PREFIX_."product` WHERE active = 1";
    $products = Db::getInstance()->executeS($sql);

    $errors = array();

    foreach ($products as $product) 
    {
      $p = new Product($product['id_product'],6);

      if ( $p->price <= 0 || $p->getPrice() <= 0 )
      {
        $error = sprintf("[ ERROR ]: %' 13s, rel. price: %' 7s, total: %' 7s\n", $p->reference, $p->price, $p->getPrice());
        echo $error."\n";
        $errors[] = $error;
        $p->active = false;
        $p->save();
        continue;
      }

      foreach ($p->getAttributeCombinaisons(6) as $combination)
      {
        $total = $p->getPrice(false) + $combination['price'];
        if ( $total <= 0 )
        {
          $error = sprintf("[ ERROR ]: Zero or negative price: %' 13s, rel. price: %' 7s, total: %' 7s", $combination['reference'], (float)$combination['price'], $total);
          echo $error."\n";
          $errors[] = $error;
          $p->active = false;
          $p->save();
          continue;
        }
      }
    }

    if ( !empty($errors) )
    {
      mail( Configuration::get(PS_SHOP_EMAIL),'Import error',implode("\n",$errors));
    }
  }
}
