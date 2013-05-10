<?php

/**
 * undocumented class
 *
 * @packaged default
 * @author Henrik Farre <hf@bellcom.dk>
 **/
class Category extends CategoryCore
{
  public $external_id;

  /**
   * undocumented function
   *
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public function getFields()
  {
    $fields = parent::getFields();
    $fields['external_id'] = pSQL($this->external_id);
    return $fields;
  }

  /**
   * undocumented function
   *
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public static function getByExternalID( $extID )
  {
    $sql = "SELECT id_category FROM `"._DB_PREFIX_."category` WHERE external_id = '". pSQL($extID) ."'";

    $result = Db::getInstance()->getValue($sql);

    if (!$result)
    {
      return false;
    }
    
    return new Category( $result );
  }
} // END class Customer extends CustomerCore
