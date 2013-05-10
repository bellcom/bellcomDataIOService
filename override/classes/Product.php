<?php

/**
 * undocumented class
 *
 * @packaged default
 * @author Henrik Farre <hf@bellcom.dk>
 **/
class Product extends ProductCore
{
  public $external_id; // Identifies the product using an external id (C5, AX, whatever)
  public $updated; // Used by the import to check if the product has been updated

  /**
   * __construct
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public function __construct($id_product = NULL, $full = false, $id_lang = NULL)
  {
    $this->fieldsValidateLang['description']       = 'isCleanHTML';
    $this->fieldsValidateLang['description_short'] = 'isCleanHTML';
    
	parent::__construct($id_product, $full, $id_lang);
  }

  /**
   * undocumented function
   *
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public function getFields()
  {
    $fields                = parent::getFields();
    $fields['external_id'] = pSQL($this->external_id);
    $fields['updated']     = (int) $this->updated;
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
    $sql = "SELECT id_product FROM `"._DB_PREFIX_."product` WHERE external_id = '". pSQL($extID) ."'";

    $result = Db::getInstance()->getValue($sql);

    if (!$result)
    {
      return false;
    }
    
    return new Product( $result );
  }

  /**
   * undocumented function
   *
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public static function getExternalIDByID( $ID )
  {
    $sql = "SELECT external_id FROM `"._DB_PREFIX_."product` WHERE id_product = '". pSQL($ID) ."'";
    
    return Db::getInstance()->getValue($sql);
  }

  /**
   * getIDByExternalID
   * @return mixed int or false
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public static function getIDByExternalID( $extID )
  {
    $sql = "SELECT id_product FROM `"._DB_PREFIX_."product` WHERE external_id = '". pSQL($extID) ."'";
    
    return Db::getInstance()->getValue($sql);
  }

  /**
   * getExternalIDByRef
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public static function getExternalIDByRef($ref)
  {
    $sql = "SELECT external_id FROM `"._DB_PREFIX_."product` WHERE reference = '". pSQL($ref) ."'";
    
    return Db::getInstance()->getValue($sql);
  }
} // END class Product extends ProductCore
