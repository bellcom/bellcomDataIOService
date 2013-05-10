<?php

namespace Bellcom;
use Pimple;
use Exception;
use Search;

/**
 * undocumented class
 *
 * @packaged default
 * @author Henrik Farre <hf@bellcom.dk>
 **/
class processorSearchIndexation
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
    Search::indexation(true);
  }
}
