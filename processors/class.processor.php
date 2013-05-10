<?php
namespace Bellcom;
use Pimple;

/**
 * undocumented class
 *
 * @packaged default
 * @author Henrik Farre <hf@bellcom.dk>
 **/
class processor
{
  const PROCESS_EXTRACT = 10;
  const PROCESS_ENCODING = 20;
  const PROCESS_FIX_CATEGORY_GROUP_PERMISSIONS = 30;
  const PROCESS_CATEGORY_NTREE = 40;
  const PROCESS_SEARCH_INDEXATION = 50;
  const PROCESS_CLEAN_UP = 60;
  const PROCESS_CHECK_FILES = 70;
  const PROCESS_CLEAN_MISSING_PRODUCTS = 80;
  const PROCESS_DISABLE_PRODUCTS_WITH_WRONG_PRICE = 90;
  const PROCESS_MAIL_STATUS = 100;

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
  public function run()
  {
    $this->app['processor']->process();
  }
} // END class fetcher
