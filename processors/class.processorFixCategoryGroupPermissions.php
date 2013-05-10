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
class processorFixCategoryGroupPermissions
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
    $sql = "SELECT id_category FROM ps_category";
    $categoryIDs = Db::getInstance()->ExecuteS($sql);

    $sql = "SELECT id_group FROM ps_group";
    $groupIDs = Db::getInstance()->ExecuteS($sql);

    $values = array();

    foreach ( $categoryIDs as $cid )
    {
      $sql = "INSERT IGNORE INTO ps_category_group (id_category, id_group) VALUES ";
      foreach ( $groupIDs as $gid )
      {
        $values[] = "({$cid['id_category']},{$gid['id_group']})";
      }
      $sql .= implode(',',$values);
      Db::getInstance()->ExecuteS($sql);
    }
  }
}
