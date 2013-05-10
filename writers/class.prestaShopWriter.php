<?php

namespace Bellcom;
use Language;
use Configuration;
use Image;
use ImageType;
use Module;

/**
 * undocumented class
 *
 * @packaged default
 * @author Henrik Farre <hf@bellcom.dk>
 **/
class prestaShopWriter extends writer
{
  /**
   * Ripped from admin/tabs/AdminImport.php
   *
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  protected static function createMultiLangField($field)
  {
	$languages = Language::getLanguages(false);
	$res = array();
	foreach ($languages AS $lang)
	  $res[$lang['id_lang']] = $field;
	return $res;
  }

  /**
   * Copy of funtion in admin/tabs/AdminImport.php
   *
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  protected static function copyImg($id_entity, $id_image = NULL, $url, $entity = 'products')
  {
    $tmpfile = tempnam(_PS_TMP_IMG_DIR_, 'ps_import');
    $watermark_types = explode(',', Configuration::get('WATERMARK_TYPES'));

    switch($entity)
    {
      default:
      case 'products':
        $path = _PS_PROD_IMG_DIR_.(int)($id_entity).'-'.(int)($id_image);
        break;
      case 'categories':
        $path = _PS_CAT_IMG_DIR_.(int)($id_entity);
        break;
    }

    if (copy(trim($url), $tmpfile))
    {
      imageResize($tmpfile, $path.'.jpg');
      $imagesTypes = ImageType::getImagesTypes($entity);
      foreach ($imagesTypes as $k => $imageType)
      {
        imageResize($tmpfile, $path.'-'.stripslashes($imageType['name']).'.jpg', $imageType['width'], $imageType['height']);
        if (in_array($imageType['id_image_type'], $watermark_types))
        {
          Module::hookExec('watermark', array('id_image' => $id_image, 'id_product' => $id_entity));
        }
      }
    }
    else
    {
      unlink($tmpfile);
      return false;
    }
    unlink($tmpfile);
    return true;
  }

} // END class writer
