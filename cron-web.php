<?php

$argument = 'test.php';

$baseDir = str_replace('modules/bellcomDataIOService','', exec('pwd')); // getcwd(); if used in a symlinked directory will return the target of the symlink
$baseDir = str_replace('local','public_html', $baseDir);

require realpath($baseDir.'/config/config.inc.php');
require dirname(__FILE__).'/class.bellcomDataIOServiceEngine.php';

$_SERVER['HTTP_HOST'] = 'localhost.localdomain';
$_SERVER['REQUEST_METHOD'] = 'GET';

if ( !isset($cookie) )
{
  $_SERVER['HTTP_HOST'] = 'localhost.localdomain';
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
$engine->cronTask( $argument );
