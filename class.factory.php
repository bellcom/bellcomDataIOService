<?php
namespace Bellcom;
use Pimple;
use bellcomDataIOService;
use Exception;

/**
 * undocumented class
 *
 * @packaged default
 * @author Henrik Farre <hf@bellcom.dk>
 **/
class factory
{
  private static $instance = null;
  protected $app = null;

  private function __construct() 
  {
  }

  public static function getInstance( $app )
  {
    if ( self::$instance === null )
    {
      self::$instance = new self;
      self::$instance->app = $app;
    }

    return self::$instance;
  }

  /**
   * undocumented function
   *
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public function build( $type, array $config )
  {
    switch ($type) 
    {
      case bellcomDataIOServiceEngine::IMPORT:
        $this->app['importConfig'] = $config;

        $this->app['mapper'] = $this->app->share(function($c) {
          return new mapper($c);
        });
        $this->app['validator'] = $this->app->share(function($c) {
          return new validator($c);
        });

        $importer = new importer($this->app);
        return $importer;
        break;

      case 'readerCSV':
        $this->app['readerConfig'] = $config;
        return new readerCSV($this->app);
        break;

      case 'writerCSV':
        $this->app['writerConfig'] = $config;
        return new writerCSV($this->app);
        break;

      case bellcomDataIOServiceEngine::FETCH:
        $this->app['transportConfig'] = $config;
        $this->app['dataTransport'] = $this->app->share(function($c) {
          return new ftpTransport($c);
        });

        $fetcher = new fetcher($this->app);
        return $fetcher;
        break;
      case bellcomDataIOServiceEngine::PROCESS:
        $this->app['processConfig'] = $config;
        
        switch ($config['process_type']) 
        {
          case processor::PROCESS_EXTRACT:
            $this->app['processor'] = $this->app->share(function($c) {
              return new processorExtractor($c);
            });
            break;
          case processor::PROCESS_ENCODING:
            $this->app['processor'] = $this->app->share(function($c) {
              return new processorEncoding($c);
            });
            break;
          case processor::PROCESS_FIX_CATEGORY_GROUP_PERMISSIONS:
            $this->app['processor'] = $this->app->share(function($c) {
              return new processorFixCategoryGroupPermissions($c);
            });
            break;
          case processor::PROCESS_CATEGORY_NTREE:
            $this->app['processor'] = $this->app->share(function($c) {
              return new processorCategoryNtree($c);
            });
            break;
          case processor::PROCESS_SEARCH_INDEXATION:
            $this->app['processor'] = $this->app->share(function($c) {
              return new processorSearchIndexation($c);
            });
            break;
          case processor::PROCESS_CLEAN_UP:
            $this->app['processor'] = $this->app->share(function($c) {
              return new processorCleanUp($c);
            });
            break;
          case processor::PROCESS_CHECK_FILES:
            $this->app['processor'] = $this->app->share(function($c) {
              return new processorCheckFiles($c);
            });
            break;
          case processor::PROCESS_CLEAN_MISSING_PRODUCTS:
            $this->app['processor'] = $this->app->share(function($c) {
              return new processorCleanMissingProducts($c);
            });
            break;
          case processor::PROCESS_DISABLE_PRODUCTS_WITH_WRONG_PRICE:
            $this->app['processor'] = $this->app->share(function($c) {
              return new processorDisableProductsWithWrongPrice($c);
            });
            break;
          case processor::PROCESS_MAIL_STATUS:
            $this->app['processor'] = $this->app->share(function($c) {
              return new processorMailStatus($c);
            });
            break;
        }

        $processor = new processor($this->app);
        return $processor;
        break;
      case bellcomDataIOServiceEngine::EXPORT:
        $this->app['exportConfig'] = $config;

        $exporter = new exporter($this->app);
        return $exporter;
        break;
      case bellcomDataIOServiceEngine::CUSTOM:
        require dirname(__FILE__).'/'.$config['custom']['file'];
        $className = __NAMESPACE__.'\\'.$config['custom']['class'];

        $custom = new $className($this->app);
        return $custom;
        break;
      default:
        throw new Exception('Does not know how to build task of type "'.$type.'"');
        break;
    }
  }
} // END class factory
