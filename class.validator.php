<?php

namespace Bellcom;
use Pimple;
use Exception;
use Validate;

class validator
{
  private $app = null;
  private $validators = array();

  public function __construct(Pimple $app)
  {
    $this->app = $app;
    $this->validators = array(
      'not_empty' => function( $data ) {
        $retval = ( ( $data === 0 || $data === '0' ) ? true : !empty($data) );
        return $retval;
      },
      'empty' => function( $data ) {
        $retval = ( ( $data === 0 || $data === '0' ) ? true : !empty($data) );
        //echo "not_empty: '".$data."' ".($retval ? 'true' : 'false')."\n";
        return !$retval;
      },
      'email' => function( $data ) {
        $retval = Validate::isEmail($data);
        //echo "email    : '".$data."' ".($retval ? 'true' : 'false')."\n";
        return $retval;
      },
      'passwd' => function( $data ) {
        return Validate::isPasswd($data);
      },
      'name' => function( $data ) {
        return Validate::isName($data);
      },
      'larger_than_zero' => function( $data ) {
        return ($data > 0) ? true : false;
      },
      'ctype_digit' => function( $data ) {
        return ctype_digit($data);
      },
    );
  }

  public function validate( $data, array $methods )
  {
    foreach ( $methods as $validator )
    {
      if ( !isset($this->validators[$validator]) )
      {
        throw new Exception( 'Unknown validator function "'. $validator .'"' );
      }

      if ( !$this->validators[$validator]( $data ) )
      {
        throw new Exception( 'Failed validator "'.$validator.'", it contains: "'.$data .'"'  );
      }
    }
  }
}
