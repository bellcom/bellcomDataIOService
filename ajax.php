<?php

$baseDir = $_SERVER['DOCUMENT_ROOT'];
require $baseDir.'/config/config.inc.php';
require $baseDir.'/init.php';
require $baseDir.'/modules/bellcomDataIOService/class.bellcomDataIOServiceEngine.php';
header( 'Cache-Control: no-store, no-cache, must-revalidate' );
header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );
header( 'Content-type: application/json; charset=utf-8' );
header( 'Pragma: no-cache' );

if (!Tools::getValue('ajax') || Tools::getValue('token') != sha1(_COOKIE_KEY_.'ajaxBellcomDataIOService') )
{
  header('HTTP/1.1 500 Internal Server Error');
  die( json_encode('No access') );
}

set_time_limit(0);
ini_set('max_execution_time', 0);

$method = Tools::getValue('method');
$module = new \Bellcom\bellcomDataIOServiceEngine();

switch ($method)
{
  case 'getConfigList':
    die( json_encode( $module->getConfigList() ) );
    break;
  case 'startImport':
    die( json_encode( $module->startImport( $_POST ) ) );
    break;
}
