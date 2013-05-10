<?php

/**
 * undocumented class
 *
 * @packaged default
 * @author Henrik Farre <hf@bellcom.dk>
 **/
class Group extends GroupCore
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
    $sql = "SELECT id_group FROM `"._DB_PREFIX_."group` WHERE external_id = '". pSQL($extID) ."'";

    $result = Db::getInstance()->getValue($sql);

    if (!$result)
    {
      return false;
    }
    
    return new Group( $result );
  }

  /**
   * undocumented function
   *
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public static function getExternalIDByID( $ID )
  {
    $sql = "SELECT external_id FROM `"._DB_PREFIX_."group` WHERE id_group = '". pSQL($ID) ."'";
    
    return Db::getInstance()->getValue($sql);
  }
} // END class Group extends GroupCore 
