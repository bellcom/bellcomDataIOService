<?php
/**
 * Provides functions to import/export data from Prestashop
 *
 * @packaged bellcom
 * @author Henrik Farre <hf@bellcom.dk>
 **/
class bellcomDataIOService extends Module
{
  public function __construct()
  {
    $this->name = 'bellcomDataIOService';
    $this->version = '0.2';
    $this->author = 'Bellcom Open Source';

    parent::__construct();

    $this->displayName = $this->l('Bellcom Data In/Out Service');
    $this->description = $this->l('Provides access to data in/out service');
  }

  /**
   * 
   */
  public function install()
  {
    if (
      parent::install() === false
      ||
      $this->addAdminTab() === false
      ||
      $this->addDbFields() === false
      ||
      $this->registerHook( 'paymentConfirm' ) === false
      ||
      $this->registerHook( 'orderConfirmation' ) === false
      )
    {
      return false;
    }

    return true;
  }

  /**
   */
  public function uninstall()
  {
    if ( 
      parent::uninstall() === false
      ||
      $this->removeAdminTab() === false
      ||
      $this->dropDbFields() === false
      ||
      $this->unregisterHook( 'paymentConfirm' ) === false
      ||
      $this->unregisterHook( 'orderConfirmation' ) === false
    )
    {
      return false;
    }

    return true;
  }

  /**
   * addAdminTab
   * @return bool
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  protected function addAdminTab()
  {
	$langs = Language::getLanguages();

	$tab = new Tab();
	$tab->class_name = "AdminBellcomDataIOService";
	$tab->module = $this->name;
	$tab->id_parent = Tab::getIdFromClassName( 'AdminCatalog' );

	foreach( $langs as $l ) 
    {
      $tab->name[ $l['id_lang'] ] = "Import";
    }

	$id = $tab->add(true,false);

    return true;
  }

  /**
   * removeAdminTab
   * @return bool
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  protected function removeAdminTab()
  {
	$tabID = Tab::getIdFromClassName("AdminBellcomDataIOService");

	if ( $tabID )
    {
	  $tab = new Tab( $tabID );
	  $tab->delete();
	}

    return true;
  }

  /**
   * addDbFields
   * @TODO: check db return status
   * @return bool
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public function addDbFields()
  {
    Db::getInstance()->Execute("ALTER TABLE `"._DB_PREFIX_."category` ADD `external_id` VARCHAR( 255 ) NULL");
    Db::getInstance()->Execute("ALTER TABLE `"._DB_PREFIX_."product` ADD `external_id` VARCHAR( 255 ) NULL");
    Db::getInstance()->Execute("ALTER TABLE `"._DB_PREFIX_."product` ADD `updated` TINYINT( 1 ) NOT NULL DEFAULT '0'");
    Db::getInstance()->Execute("ALTER TABLE `"._DB_PREFIX_."orders` ADD `external_status` VARCHAR( 255 ) NULL");
    Db::getInstance()->Execute("ALTER TABLE `"._DB_PREFIX_."group` ADD `external_id` VARCHAR( 255 ) NULL");
    Db::getInstance()->Execute("ALTER TABLE `"._DB_PREFIX_."customer` ADD `external_id` VARCHAR( 255 ) NULL");
    return true;
  }

  /**
   * dropDbFields
   * @TODO: check db return status
   * @return bool
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public function dropDbFields()
  {
    Db::getInstance()->Execute("ALTER TABLE `"._DB_PREFIX_."category` DROP `external_id`");
    Db::getInstance()->Execute("ALTER TABLE `"._DB_PREFIX_."product` DROP `external_id`");
    Db::getInstance()->Execute("ALTER TABLE `"._DB_PREFIX_."product` DROP `updated`");
    Db::getInstance()->Execute("ALTER TABLE `"._DB_PREFIX_."orders` DROP `external_status`");
    Db::getInstance()->Execute("ALTER TABLE `"._DB_PREFIX_."group` DROP `external_id`");
    Db::getInstance()->Execute("ALTER TABLE `"._DB_PREFIX_."customer` DROP `external_id`");
    return true;
  }

  public function getContent()
  {
    return 'Test';
  }

  /**
   * hookPaymentConfirm
   * @return bool
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public function hookPaymentConfirm( $params )
  {
    return '';
    $orderID = $params['id_order'];

    $hookParams = array( 
      'order_id' => $orderID,
      );

    $this->execHookHandler( 'paymentConfirm', $hookParams );
  }

  /**
   * hookOrderConfirmation
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public function hookOrderConfirmation( $params )
  {
    return '';
    $orderID = $params['objOrder']->id;

    $hookParams = array( 
      'order_id' => $orderID,
      );

    $this->execHookHandler( 'orderConfirmation', $hookParams );
  }

  /**
   * execHookHandler
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public function execHookHandler( $hookName, $params )
  {
    require dirname(__FILE__).'/class.bellcomDataIOServiceEngine.php';

    $engine = new \Bellcom\bellcomDataIOServiceEngine();
    $engine->handleHook( $hookName, $params );
  }
}
