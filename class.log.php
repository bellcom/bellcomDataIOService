<?php

namespace Bellcom;

class log
{
  private $logLevel = 0;
  const INFO    = 5;
  const ERROR   = 10;
  const WARNING = 20;
  const DEBUG   = 30;
  const TIMING  = 40;

  public function __construct($logLevel = self::ERROR)
  {
    $this->logLevel = $logLevel;
  }

  public function msg( $msg, $level = self::ERROR )
  {
    if ( $this->logLevel >= $level )
    {
      self::writeMsg( self::format( $msg, $level ) );
    }
  }

  public static function staticMsg( $msg, $level = self::ERROR ) 
  {
    self::writeMsg( self::format( $msg, $level ) );
  }

  private static function writeMsg( $msg )
  {
    error_log( $msg );
  }

  public static function info( $msg )
  {
    self::writeMsg( self::format($msg, self::INFO) );
  }

  public static function error( $msg )
  {
    self::writeMsg( self::format($msg, self::ERROR) );
  }

  public static function warning( $msg )
  {
    self::writeMsg( self::format($msg, self::WARNING) );
  }

  public static function debug( $msg )
  {
    self::writeMsg( self::format($msg, self::DEBUG) );
  }

  private static function format( $msg, $level )
  {
    switch ($level) 
    {
      case self::INFO:
        return '[ INFO  ] '.$msg;
        break;
      case self::ERROR:
        return '[ ERROR ] '.$msg;
        break;
      case self::WARNING:
        return '[WARNING] '.$msg;
        break;
      case self::DEBUG:
        return '[ DEBUG ] '.$msg;
        break;
      case self::TIMING:
        return '['.mktime().'] '.$msg;
        break;
    }
  }
}
