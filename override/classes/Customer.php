<?php

/**
 * undocumented class
 *
 * @packaged default
 * @author Henrik Farre <hf@bellcom.dk>
 **/
class Customer extends CustomerCore
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
    $sql = "SELECT id_customer FROM `"._DB_PREFIX_."customer` WHERE external_id = '". pSQL($extID) ."'";

    $result = Db::getInstance()->getValue($sql);

    if (!$result)
    {
      return false;
    }
    
    return new Customer( $result );
  }

  /**
   * undocumented function
   *
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public static function getExternalIDByID( $ID )
  {
    $sql = "SELECT external_id FROM `"._DB_PREFIX_."customer` WHERE id_customer = '". pSQL($ID) ."'";
    
    return Db::getInstance()->getValue($sql);
  }
} // END class Customer extends CustomerCore
