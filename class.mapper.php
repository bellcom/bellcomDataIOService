<?php

namespace Bellcom;
use Pimple;
use Configuration;
use Exception;

class mapper
{
  private $app = null;
  public $mapping = array();
  private $defaults = array();
  private $type = null;

  public function __construct(Pimple $app)
  {
    $this->app = $app;
    $this->writer = $this->app['readerConfig']['writer'];
    $this->mapping = $this->app['readerConfig']['mapping'];
    $this->params = (isset($this->app['readerConfig']['params'])) ? $this->app['readerConfig']['params'] : array();
    $this->postImport = (isset($this->app['readerConfig']['post_import'])) ? $this->app['readerConfig']['post_import'] : false;
	$this->defaults['language'] = Configuration::get('PS_LANG_DEFAULT');
  }

  /**
   * Remaps an array to array with named keys
   *
   * @return object
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public function map( array $data )
  {
    $mappedData = array();
    $afterFunctions = array();

    foreach( $this->mapping as $key => $map )
    {
      $skip = false; // if something sets $skip = true, the single field is skipped

      try
      {
        $this->validate( $map, $data, $key );
      }
      catch ( Exception $e )
      {
        $message = $e->getMessage().", original CSV data: ". implode(';', $data)."\n";
        throw new Exception( $message );
      }

      $value = '';
      if ( isset($map['field']) ) // Data from a single field
      {
        $value = $this->transform( $data[ $map['field'] ], $map );
      }
      elseif ( isset($map['fields']) ) // Data from multiple fields are mapped 
      {
        $value = array();
        // if no $index has been set it will default to 0,1,2...
        foreach ($map['fields'] as $index => $fieldIndex ) 
        {
          $value[$index] = $data[$fieldIndex];
        }
        $value = $this->transform( $value, $map );
      }
      elseif ( isset($map['fields_combine']) ) // Data from multiple fields are combined
      {
        $value = $this->combineFields( $map, $data, $key );
        $value = $this->transform( $value, $map );
      }
      elseif ( isset($map['value']) )
      {
        $value = $map['value']; 
      }
      // callbacks should be defined in helperFunctions.php
      elseif ( isset($map['callback']) && is_callable($map['callback']) )
      {
        if ( !isset($map['meta']['sequence']) || ( isset($map['meta']['sequence']) && $map['meta']['sequence'] != 'after' ) )
        {
          // NOTE: here we have only the "raw" data array, 
          // the after functions get the returnArray as input
          $value = call_user_func( $map['callback'], $value );
        }
        elseif ( isset($map['meta']['sequence']) && $map['meta']['sequence'] == 'after' )
        {
          $skip = true;
          $afterCallbacks[$key] = $map['callback'];
        }
      }
      // DEPRECATED! use 'callback' instead as it can be serialized
      elseif( isset($map['function']) && is_callable($map['function']) )
      {
        // In case we want the function to operate on the processed data, set its meta sequence to after
        if ( !isset($map['meta']['sequence']) || ( isset($map['meta']['sequence']) && $map['meta']['sequence'] != 'after' ) )
        {
          // NOTE: here we have only the "raw" data array, 
          // the after functions get the returnArray as input
          $value = $map['function']( $data );
        }
        elseif ( isset($map['meta']['sequence']) && $map['meta']['sequence'] == 'after' )
        {
          $afterFunctions[$key] = $map['function'];
        }
      }

      if ( !$skip )
      {
        $mappedData[$key] = $value;
      }
    }

    $returnArray = array ( 
      'writer'     => $this->writer, 
      'mappedData' => $mappedData, 
      'data'       => $data, 
      'defaults'   => $this->defaults, 
      'mapping'    => $this->mapping, 
      'params'     => $this->params,
      'postImport' => $this->postImport,
    );

    // Post processintype
    if ( !empty($afterCallbacks) )
    {
      foreach ($afterCallbacks as $key => $func) 
      {
        try
        {
          $value = call_user_func( $func, $returnArray );
          if ( is_array( $value ))
          {
            foreach ($value as $key2 => $value2) 
            {
              $returnArray['mappedData'][$key2] = $value2;
            }
          }
          else
          {
            $returnArray['mappedData'][$key] = $value;
          }
        }
        catch ( Exception $e )
        {
          $message = 'A callback failed: '. $key .' => '. $e->getMessage();
          $this->app['log']->msg( $message, log::ERROR );
          throw new Exception( $message );
        }
      }
    }

    // Deprecated, use callbacks
    if ( !empty($afterFunctions) )
    {
      //throw new Exception( 'Closures are not supported any longer' );
      echo '[WARNING]: Closures are not supported any longer'.PHP_EOL;
      foreach ($afterFunctions as $key => $func) 
      {
        try
        {
          $value = $func( $returnArray);
          //echo "After functions: ". $key .' => '. $value ."\n";
          //echo "Before: \n";
          //print_r($returnArray['mappedData']);
          $returnArray['mappedData'][$key] = $value;
          //echo "After: \n"])
          //print_r($returnArray['mappedData']);
        }
        catch ( Exception $e )
        {
          $message = 'A function failed: '. $key .' => '. $e->getMessage();
          $this->app['log']->msg( $message, log::ERROR );
          throw new Exception( $message );
        }
      }
    }

    // If something decides to set skip to true, the mapping should not be processed
    if ( isset($returnArray['mappedData']['skip']) && $returnArray['mappedData']['skip'] === true)
    {
      return false;
    }

    return $returnArray;
  }

  /**
   * undocumented function
   *
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  private function validate( $map, $data, $key )
  {
    if ( isset($map['validators'] ))
    {
      try 
      {
        $this->app['validator']->validate( $data[ $map['field']  ], $map['validators'] );
      }
      catch ( Exception $e )
      {
        throw new Exception( '[ ERROR ] Field (key: "'. $key .'") does not validate: '. $e->getMessage() );
      }
    }
  }

  /**
   * undocumented function
   *
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  private function combineFields( $map, $data, $key )
  {
    $fields = $map['fields_combine'];
    $separator = (isset($map['meta']['separator'])) ? $map['meta']['separator'] : '';

    foreach ( $fields as $field )
    {
      $values[] = $data[$field];
    }

    switch ($separator) 
    {
      case 'serialize':
        return serialize($values);
        break;
      
      default:
        return implode($separator,$values);
        break;
    }

  }

  /**
   * undocumented function
   *
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  private function transform( $value, $map )
  {
    if ( !isset($map['transform']) )
    {
      return $value;
    }

    foreach ( $map['transform'] as $key => $method )
    {
      if ( is_array($method) )
      {
        $args = $method;
        $method = $key;
      }

      switch ($method) 
      {
        case 'convert_to_int':
        case 'strip_leading_zero':
          $value = (int) $value;
          break;
        case 'danish_number_to_float':
          $tmp = str_replace('.','', $value); // 2.000,00 => 2000,00
          $tmp = str_replace(',','.', $tmp); // 2000,00 => 2000.00
          $value = (float) $tmp;
          break;
        case 'strtolower':
        case 'lower_case':
          $value = mb_strtolower($value, 'UTF-8');
          break;
        case 'ucfirst':
        case 'upper_case_first':
          $value = mb_strtoupper(mb_substr($value, 0, 1),'UTF-8') . mb_substr($value, 1);
          break;
        case 'replace_chars':
          $search = array_keys($args);
          $replace = array_values($args);

          if ( is_array($value) ) 
          {
            array_walk($value, function(&$item,$key) { utf8_decode($item); });
          }
          else
          {
            $value = utf8_decode($value);
          }

          $value = str_replace($search, $replace, $value);

          if ( is_array($value) ) 
          {
            array_walk($value, function(&$item,$key) { utf8_encode($item); });
          }
          else 
          {
            $value = utf8_encode($value);
          }
          break;
        case 'trim':
          $value = trim($value);
          break;
        case 'remove_danish_tax':
          $value = $value * 0.8;
          break;
        case 'datetime_to_date':
          list($date, $time) = explode(' ', $value);
          $value = $date;
          break;
        case 'subtract_fields':
          if ( is_array($value) )
          {
            $value = $value[0] - $value[1];
          }
          return $value;
          break;
        case 'explode':
          $separator = array_shift($args);
          $value = explode( $separator, $value );
          break;
        default:
          echo "Unknown transform method '".$method."'\n";
          break;
      }
    }

    return $value; 
  }
}
