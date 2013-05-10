<?php

namespace Bellcom;
use Tools;
use ObjectModel;
use Module;
use Configuration;
use Exception;
use Category;
use Validate;

/**
 * undocumented class
 *
 * @packaged bellcom
 * @author Henrik Farre <hf@bellcom.dk>
 **/
class prestaShopCategoryWriter extends prestaShopWriter
{

  /**
   * write
   * @return bool
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public static function write(array $data)
  {
    $categoryData   = $data['mappedData'];
    $rawData        = $data['data'];
    $defaults       = $data['defaults'];
    $mapping        = $data['mapping'];
    $params         = $data['params'];

    $fieldError     = false;
    $langFieldError = false;
    $category       = false;
    $create         = false;

    $category = Category::getByExternalID( $categoryData['external_id'] );

    if ( isset( $params['onlyUpdateObject'] ) && $params['onlyUpdateObject'] && $category === false)
    {
      throw new Exception( 'onlyUpdateObject is set, but category does not exists (external id: '. $categoryData['external_id'] .')' );
    }

    if ( !isset($category->id) || is_null( $category->id ) ) // Can't use instanceOf, because it might be extended
    {
      $create = true;
      $category = new Category();    
    }

    foreach ( $categoryData as $key => $value )
    {
      switch ($key) 
      {
        case 'name':
        case 'description':
        case 'link_rewrite':
          $category->{$key} = array();
          $category->{$key} = self::createMultiLangField($value);
          break;
          //case 'image':
          //$images[] = $value;
          //break;
        default:
          $category->{$key} = $value;
          break;
      }
    }

    if ( !isset( $categoryData['link_rewrite'] ) )
    {
      $link_rewrite = Tools::link_rewrite( (is_array($category->name)) ? $category->name[$defaults['language']] : $category->name );
      $category->link_rewrite = array();
      $category->link_rewrite[$defaults['language']] = $link_rewrite;
    }

	if (! Validate::isLinkRewrite($category->link_rewrite[$defaults['language']]) )
    {
      echo "Link rewrite not valid: '".$link_rewrite."' name: '". $category->name ."'\n";
      return false;
    }

    $fieldError = $category->validateFields(false, true);
    $langFieldError = $category->validateFieldsLang(false, true);

    if ( !($fieldError === true && $langFieldError === true) )
    {
      throw new Exception( '[ Error ] Line '.__LINE__.' in '.__FILE__ .': '. $fieldError .' / '. $langFieldError);
    }

    $category->doNotRegenerateNTree = true;

    $saveStatus = $category->save();
    return true;
  }
} // END class productWriter
