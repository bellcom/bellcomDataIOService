<?php

include_once(PS_ADMIN_DIR.'/../classes/AdminTab.php');
include_once(PS_ADMIN_DIR.'/tabs/AdminCatalog.php');
include('AdminCategoriesBellcom.php');

class AdminCatalogBellcom extends AdminCatalog
{
  /**
   * Hack into class and replace AdminCategories with own class
   *
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public function __construct()
  {
	parent::__construct();
	$this->adminCategories = new AdminCategoriesBellcom(); // Override var set by parent 
  }
}
