<?php

//include_once(PS_ADMIN_DIR.'/tabs/AdminCategories.php');

class AdminCategoriesBellcom extends AdminCategories
{
  /**
   * __construct
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public function __construct()
  {
	parent::__construct();

    // Why is this not displayed?
    $this->fieldsDisplay['external_id'] = array('title' => $this->l('Ext. ID'), 'align' => 'center', 'width' => 25);
  }

  /**
   * displayForm
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public function displayForm($token = NULL)
  {
    error_log(__LINE__.':'.__FILE__.' '); // hf@bellcom.dk debugging
    ob_start();
    parent::displayForm($token);
    $output = ob_get_clean();
    $output .= 'Test';
    echo $output;
  }
}
