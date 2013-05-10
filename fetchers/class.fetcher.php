<?php

namespace Bellcom;
use Pimple;

/**
 * Fetches data for the importer
 *
 * @packaged default
 * @author Henrik Farre <hf@bellcom.dk>
 **/
class fetcher
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
  public function run()
  {
    $this->app['dataTransport']->fetch();
  }
} // END class fetcher
