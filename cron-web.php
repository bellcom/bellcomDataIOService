<?php

$requiredArgs = array(
  'import_id',
  'key',
  );

foreach ($requiredArgs as $arg) 
{
  if ( !isset($_GET[$arg]) )
  {
    die('Missing argument');
  }
}

// Only allow localhost
if ( !in_array( $_SERVER['REMOTE_ADDR'], array( '127.0.0.1', '127.0.1.1' ) ) )
{
  die('No access');
}

$baseDir = str_replace('modules/bellcomDataIOService','', exec('pwd')); // getcwd(); if used in a symlinked directory will return the target of the symlink
$baseDir = str_replace('local','public_html', $baseDir);

require realpath($baseDir.'/config/config.inc.php');
require dirname(__FILE__).'/class.bellcomDataIOServiceEngine.php';

$secret = Configuration::get( 'BC_DATA_IO_SECRET' );

if ( $_GET['key'] != md5( $_GET['import_id'].$secret )  )
{
  die('Wrong key');
}

$importId = $_GET['import_id'];

if ( !isset($cookie) )
{
  if (!defined('_PS_BASE_URL_'))
  {
    define('_PS_BASE_URL_', Tools::getShopDomain(true));
  }

  $cookie = new Cookie('psAdmin');
}

define('UNFRIENDLY_ERROR', false);
ini_set('mbstring.internal_encoding','UTF-8');
ini_set('gd.jpeg_ignore_warning', true);

$engine = new Bellcom\bellcomDataIOServiceEngine();
$engine->cronTask( $importId );
