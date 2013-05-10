<?php
namespace Bellcom;
use Product;
use Tools;
use Feature;
use FeatureValue;
use ObjectModel;
use Configuration;
use Exception;
use Validate;
use Image;
use SpecificPrice;
use Shop;
use Group;
use Currency;
use Attribute;
use AttributeGroup;
use Supplier;

require_once _PS_ROOT_DIR_.'/images.inc.php';

/**
 * undocumented class
 *
 * @packaged bellcom
 * @author Henrik Farre <hf@bellcom.dk>
 **/
class prestaShopProductWriter extends prestaShopWriter
{
  static public function write(array $data)
  {
    static $cache   = array();

    $productData    = $data['mappedData'];
    $rawData        = $data['data'];
    $defaults       = $data['defaults'];
    $mapping        = $data['mapping'];
    $params         = $data['params'];
    $postImport     = $data['postImport'];

    $fieldError     = false;
    $langFieldError = false;
    $product        = false;
    $create         = false;

    $features       = array();
    $images         = array();
    $accessory      = array();

    $isMainProduct  = true; 
    $isVariant      = false;

    $hasAttributes  = false; 
    $attributeInfo  = array(
      'wholesale_price' => 0.00, // "Engros" price
      'price'           => 0.00, // What should the base price be increased with
      'price_abs'       => 0.00, // The absolute price, the base price is subtracted and price is set
      'id_images'       => array(),
    );
    $attributeCombinationList = array();

    $hasSpecificPrice  = false;
    $specificPriceInfo = array(
      'price'             => 0,
      'currency'          => 0,
      'group'             => 0,
      'country'           => 0,
      'reduction_price'   => 0,
      'reduction_percent' => 0,
      'reduction_type'    => 'amount',
      'reduction_from'    => '',
      'reduction_to'      => '',
      'dont_delete'       => false, // If the mapping contains specific_price_dont_delete a manual specific price will not be deleted
    );

    $hasCustomizations = false;
    $customizations    = array();

    if ( !isset($productData['external_id']) || empty($productData['external_id']))
    {
      throw new Exception( '[ ERROR ] Product does not have a external id, it can not be imported' );
    }

    // Check if this product is a variant and not a "real" product
    if ( isset($productData['variant_id']) && !empty($productData['variant_id']) && $productData['variant_id'] != $productData['external_id'] )
    {
      // if variant_id == external_id then it is the main product, but in this case it is not
      $isMainProduct = false; 
      $isVariant     = true;
      // as variant_id is the external_id of the main product, we change external_id to the current variant_id so that we fetch that product
      $productData['external_id_org'] = $productData['external_id'];
      $productData['external_id']     = $productData['variant_id'];
    }

    $product = Product::getByExternalID( $productData['external_id'] );

    if ( isset( $params['onlyUpdateObject'] ) && $params['onlyUpdateObject'] && $product === false)
    {
      throw new Exception( '[ ERROR ] Function is only allowed to update existing products, but product does not exists (external id: '. $productData['external_id'] .')' );
    }

    // Can't use instanceOf, because it might be extended
    // if the product could not be loaded and is a variant bail
    if ( $product === false && !$isVariant ) 
    {
      $create = true;
      $product = new Product();
    }
    elseif ( $product === false && $isVariant )
    {
      throw new Exception( '[ ERROR ] Main product for variant could not be loaded (external_id: '. $productData['external_id_org'] .' variant_id: '.  $productData['variant_id'] .')' ); 
    }

    foreach ( $productData as $key => $value )
    {
      switch ($key) 
      {
        case 'name':
        case 'link_rewrite':
        case 'description':
        case 'description_short':
        case 'category':
          if ( is_array($value) )
          {
            $product->id_category = $value;
            $product->id_category_default = $value[0];
          }
          else
          {
            $product->id_category[] = $value;
            $product->id_category_default = $value;
          }
          break;
        case 'quantity':
          $product->quantity = $value;
          break;
        case 'feature': // Handle one feature, for multiple use 'features'
          $meta = ( isset($mapping[$key]['meta']) ) ? $mapping[$key]['meta'] : false;
          if ( $meta === false )
          {
            continue;
          }
          $idFeature      = Feature::addFeatureImport( $meta['feature_name'] );
          $idFeatureValue = FeatureValue::addFeatureValueImport($idFeature, $value);

          $features[] = array('id' => $idFeature, 'value' => $idFeatureValue);
          break;
        case 'features':
          if ( !is_array($value) )
          {
            continue;
          }

          $meta = ( isset($mapping[$key]['meta']) ) ? $mapping[$key]['meta'] : false;
          if ( $meta === false )
          {
            continue;
          }

          foreach( $value as $index => $feature )
          {
            $idFeature      = Feature::addFeatureImport( trim( $meta['feature_names'][$index] ) ); // fields in value must match meta
            $idFeatureValue = FeatureValue::addFeatureValueImport($idFeature, trim( $feature ));
            // Set custom to 0, as addFeatureValueImport checks for that... wtf?
            $tmpFeature = new FeatureValue($idFeatureValue);
            if ( $tmpFeature->custom == 1)
            {
              $tmpFeature->custom = 0;
              $tmpFeature->save();
            }

            $features[] = array('id' => $idFeature, 'value' => $idFeatureValue);
          }
          break;
        case 'image':
          if ( is_array($value) )
          {
            $images = $value;
          }
          else
          {
            $images[] = $value;
          }
          break;
        case 'specific_price':
          $hasSpecificPrice = true;
          $specificPriceInfo['price'] = $value;
          break;
        case 'specific_price_group':
          $hasSpecificPrice = true;
          $group = Group::getByExternalID($value);
          if ( !is_object($group) )
          {
            echo "[ ERROR ]: Group with external id '".$value."' could not be found\n";
            return false;
          }
          $specificPriceInfo['group'] = $group->id;
          break;
        case 'specific_price_currency':
          $hasSpecificPrice = true;
          $specificPriceInfo['currency'] = Currency::getIdByIsoCode( $value );
          break;
        case 'specific_price_reduction_price':
          $hasSpecificPrice = true;
          $specificPriceInfo['reduction_price'] = $value;
          break;
        case 'specific_price_dont_delete':
          $hasSpecificPrice = false;
          $specificPriceInfo['dont_delete'] = true;
          break;
        case 'attributes':
          $hasAttributes = true; 

          // Find all attribute groups
          if ( !isset($cache['attributes']['groups']) || empty($cache['attributes']['groups']) )
          {
            $cache['attributes']['groups'] = AttributeGroup::getAttributesGroups( $defaults['language'] );
          }

          $attributeGroups = $cache['attributes']['groups'];

          foreach ( $attributeGroups as $attributeGroup ) 
          {
            if ( isset($value[$attributeGroup['name']]) )
            {
              $id = $attributeGroup['id_attribute_group'];

              // Get all attributes for the current group ($id)
              if ( !isset($cache['attributes']['attributes'][$id]) || empty($cache['attributes']['attributes'][$id]) )
              {
                $cache['attributes']['attributes'][$id] = AttributeGroup::getAttributes( $defaults['language'], $id );
              }

              $found = false;
              $id2 = 0;
              // Check each attribute and see if it exists
              foreach ( $cache['attributes']['attributes'][$id] as $attribute )
              {
                if ( empty($value[$attributeGroup['name']]) )
                {
                  break;
                }

                if ( trim( $attribute['name'] ) == trim( $value[$attributeGroup['name']] ) )
                {
                  $found = true;
                  $id2 = $attribute['id_attribute'];
                }
              }

              if ( !$found )
              {
                if ( !empty($value[$attributeGroup['name']]) )
                {
                  $attributeObj = new Attribute();
                  $attributeObj->id_attribute_group = $id;
                  $attributeObj->name[ $defaults['language'] ] = trim( $value[$attributeGroup['name']] );
                  $attributeObj->add();
                  $attributeCombinationList[] = $attributeObj->id;

                  $cache['attributes']['attributes'][$id][] = array(
                    'id_attribute' => $attributeObj->id,
                    'id_attribute_group' => $id,
                    'color' => '',
                    'id_lang' => $defaults['language'],
                    'name' => trim( $value[$attributeGroup['name']] ),
                  );
                }
              }
              elseif ($id2 != 0)
              {
                $attributeCombinationList[] = $id2;
              }
            }
            else
            {
              error_log(__LINE__.':'.__FILE__.' Not set'); // hf@bellcom.dk debugging
            }

            if ( empty($attributeCombinationList) )
            {
              $hasAttributes = false; 
            }
          }

          break;
        case 'attribute_wholesale_price':
          $attributeInfo['wholesale_price'] = $value;
          break;
        case 'attribute_price':
          $attributeInfo['price'] = $value;
          break;
        case 'attribute_price_abs':
          $attributeInfo['price_abs'] = (float) $value;
          break;
        case 'attribute_quantity':
          $attributeInfo['quantity'] = $value;
          break;
        case 'attribute_supplier_reference':
          $attributeInfo['supplier_reference'] = $value;
          break;
        case 'accessory':
          if ( is_array($value) )
          {
            $accessory = $value;
          }
          else
          {
            $accessory[] = $value;
          }
          break;
        case 'supplier_name':
          if ( $supplier = Supplier::getIdByName($value) )
          {
            $product->id_supplier = (int)($supplier);
          }
          // Create supplier?
          break;
        case 'supplier_id':
          $product->id_supplier = (int)($value); // Validate?
          break;
        case 'customizations':
          $hasCustomizations = true;
          // Should be an array containing array( 'name' => 'label' ),
          $customizations = $value;
          break;
        default:
          $product->{$key} = $value;
          break;
      }
    }

    log::info( "----------------------------------------------------------------------------------------");
    log::info( sprintf("Importing: %-' 15s: %-' 6s Available: %-' 3s",$product->reference,$product->price, ($product->available_for_order?'Ja':'Nej') ) );

    if ( !isset( $productData['link_rewrite'] ) )
    {
      $link_rewrite = Tools::link_rewrite( (is_array($product->name)) ? $product->name[$defaults['language']] : $product->name );
      $product->link_rewrite = array();
      $product->link_rewrite[$defaults['language']] = $link_rewrite;
    }

    if (! Validate::isLinkRewrite($product->link_rewrite[$defaults['language']]) )
    {
      log::error( "Link rewrite not valid: '".$link_rewrite."' name: '". $product->name ."'");
      return false;
    }

    $fieldError = $product->validateFields(false, true);
    $langFieldError = $product->validateFieldsLang(false, true);

    if ( !($fieldError === true && $langFieldError === true) )
    {
      throw new Exception( '[ ERROR ]: Field (product ext id: "'. $productData['external_id'] .'"): '. $fieldError .' / '. $langFieldError);
    }

    if ( $isMainProduct && !$isVariant ) // Don't update the product if it is a variant
    {
      $saveStatus = $product->save();

      if ( $saveStatus === false )
      {
        log::error("Product save failed");
        //print_r($product);
        return false;
      }
    }

    /**
     * Handle customizations
     */
    if ( $hasCustomizations && is_array($customizations))
    {
      self::handleCustomizations( $product, $customizations );
    }

    /**
     * Handle images
     */
    $imageIDs = array();
    if ( is_array($images) && sizeof($images) > 0)
    {
      if ( !$isVariant ) // Dont delete the images if the product is a variant. In the attribute section the images are loaded from the main product
      {
        log::debug( "Deleting images" );
        $product->deleteImages();
        $imageIDs = self::handleImages( $images, $product, $defaults );
        if ( empty($imageIDs) )
        {
          log::warning("Did not attach any images to product: ". $product->reference);
        }
        else
        {
          log::info("Attached images to product: ". $product->reference .": ".implode(", ",$imageIDs));
        }
      }
      else
      {
        $mainProduct = Product::getByExternalID( $productData['variant_id'] );
        $imageIDs = self::handleImages( $images, $mainProduct, $defaults );
        if ( empty($imageIDs) )
        {
          log::warning("Did not attach any images to main product: ". $mainProduct->reference);
        }
        else
        {
          log::info("Attached images to main product: ". $mainProduct->reference .": ".implode(", ",$imageIDs));
        }
      }
    }
    else
    {
      log::warning("No images on product");
    }

    /**
     * Handle attributes
     */
    if ( !$isVariant ) // Should only be done once on the main product
    {
        $product->deleteProductAttributes();
    }
    if ( $hasAttributes && !empty($attributeCombinationList) )
    {
      //echo " > Has attributes\n";
      $existingCombinations = $product->getAttributeCombinaisons($defaults['language']);
      $reference = isset( $productData['reference'] ) ? $productData['reference'] : $productData['external_id_org'];
      if ( !isset($mainProduct) || $mainProduct->external_id != $productData['variant_id'] )
      {
        $mainProduct = Product::getByExternalID( $productData['variant_id'] );
      }

      /**
       * Handle images for attribute combinations
       */
      if ( is_array($imageIDs) && !empty($imageIDs) )
      {
        $attributeInfo['id_images'] = $imageIDs;
      }

      if ( !empty( $existingCombinations ) && is_array($existingCombinations) )
      {
        $found = false;
        foreach ($existingCombinations as $existingCombination) 
        {
          if ( $existingCombination['reference'] == $reference )
          {
            //echo " > Existing combination\n";
            static::handleAttribute($attributeCombinationList, $reference, $mainProduct, $product, $defaults, $attributeInfo, $existingCombination['id_product_attribute'], true );
            $found = true;
            break;
          }
        }

        if ( !$found )
        {
          //echo " > Did not find combination, creating new\n";
          static::handleAttribute($attributeCombinationList, $reference, $mainProduct, $product, $defaults, $attributeInfo, false, false );
        }
      }
      else
      {
        //echo " > Product does not have any combinations, creating new\n";
        static::handleAttribute($attributeCombinationList, $reference, $mainProduct, $product, $defaults, $attributeInfo, false, false );
      }
    }

    /**
     * If attributes has been set and the product is a variant, just exit
     * This way categories, features, specificprices and more are skipped
     */
    if ( !$isMainProduct && $isVariant ) 
    {
      //echo " > is variant\n";
      return true; 
    }
    /**
     * -------------------------------------------------------------------------------------------------------------------------------------------
     */

    /**
     * Handle categories
     */
    if ( !$create && isset($product->id_category) )
    {
      $product->addToCategories(array_map('intval', $product->id_category));
    }
    elseif ( $create && isset($product->id_category) )
    {
      $product->updateCategories(array_map('intval', $product->id_category));
    }

    /**
     * Handle features
     */
    if ( !empty($features) )
    {
      foreach ($features as $feature) 
      {
        Product::addFeatureProductImport($product->id, $feature['id'], $feature['value']);
      }
    }

    /**
     * Handle specific prices
     */ 
    if ( $hasSpecificPrice !== false )
    {
      $specificPrice = SpecificPrice::getSpecificPrice( $product->id, (int)Shop::getCurrentShop(), $specificPriceInfo['currency'], $specificPriceInfo['country'], $specificPriceInfo['group'], 1);

      if ( $specificPrice === false )
      {
        $specificPrice = new SpecificPrice();
      }
      elseif ( is_array( $specificPrice ) )
      {
        $specificPrice = new SpecificPrice( $specificPrice['id_specific_price'] );
      }

      if ( isset($specificPriceInfo['reduction_price']) && $specificPriceInfo['reduction_price'] < 0 ) // Negative price
      {
        log::error("Specific price is negative!: ".$specificPriceInfo['reduction_price']);
        $specificPrice = false;
      }

      if ( is_object($specificPrice) )
      {
        // Based on code from tabs/AdminImport.php
        $specificPrice->id_product     = (int)($product->id);
        $specificPrice->id_shop        = (int)(Shop::getCurrentShop());
        $specificPrice->id_currency    = $specificPriceInfo['currency'];
        $specificPrice->id_country     = $specificPriceInfo['country'];
        $specificPrice->id_group       = $specificPriceInfo['group'];
        $specificPrice->price          = $specificPriceInfo['price'];
        $specificPrice->from_quantity  = 1;
        $specificPrice->reduction      = (isset($specificPriceInfo['reduction_price']) AND $specificPriceInfo['reduction_price']) ? $specificPriceInfo['reduction_price'] : $specificPriceInfo['reduction_percent'] / 100;
        $specificPrice->reduction_type = $specificPriceInfo['reduction_type'];
        $specificPrice->from           = (isset($specificPriceInfo['reduction_from']) AND Validate::isDate($specificPriceInfo['reduction_from'])) ? $specificPriceInfo['reduction_from'] : '0000-00-00 00:00:00';
        $specificPrice->to             = (isset($specificPriceInfo['reduction_to']) AND Validate::isDate($specificPriceInfo['reduction_to'])) ? $specificPriceInfo['reduction_to'] : '0000-00-00 00:00:00';
        if (!$specificPrice->save())
        {
          log::error("Adding specificPrice, product id: ". $product->id);
        }
      }
      else
      {
        log::error("Specific price is not an object");
      }
    }
    else
    {
      if ( $specificPriceInfo['dont_delete'] !== true )
      {
        SpecificPrice::deleteByProductId((int)($product->id));
      }
    }

    /**
     * Handle accessories
     * Must be ext id's
     */
    $product->deleteAccessories();

    if ( !empty( $accessory ) )
    {
      $accessoryIDs = array();
      foreach ($accessory as $accessoryExtID) 
      {
        if ( empty($accessoryExtID) )
        {
          continue;
        }
        $accessoryIDs[] = Product::getIDByExternalID( $accessoryExtID );
      }
      if ( !empty($accessoryIDs) )
      {
        $product->changeAccessories($accessoryIDs);
      }
    }

    // TODO: 
    // - should be moved to own step as an processor
    // - somehow has to know what products to perform an action on
    // Post processing of product
    if ( $postImport !== false && is_callable($postImport))
    {
      // Use callbacks:
      call_user_func( $postImport, $product );
    }

    return true;
  }

  /**
   * handleAttribute
   *
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public static function handleAttribute( $attributeCombinationList, $reference, &$mainProduct, &$product, $defaults, array $attributeInfo, $existingID, $update = false )
  {
    //echo " > Handling attributes\n";
    /**
     * If price_abs is set, we need to subtract it from the base price
     */
    if ( $attributeInfo['price_abs'] != 0.00 )
    {
      $mainProductPrice = (float) $mainProduct->getPrice(false); // Handles if the product has an special price, without tax!!

      $calculatedPrice = 0.00;

      if ( $attributeInfo['price_abs'] > $mainProductPrice )
      {
        $calculatedPrice  = round( ( $attributeInfo['price_abs'] - $mainProductPrice ), 4 );
      }
      elseif ( $mainProductPrice > $attributeInfo['price_abs'] )
      {
        $calculatedPrice  = ( round( ( $mainProductPrice - $attributeInfo['price_abs'] ), 4 ) ) * -1;
      }
      $attributeInfo['price'] = $calculatedPrice;
    }

    if ( isset($attributeInfo['quantity']) )
    {
      $quantity = $attributeInfo['quantity'];
    }
    else
    {
      $quantity = false;
    }

    if ( isset($attributeInfo['supplier_reference']) )
    {
      $supplierReference = $attributeInfo['supplier_reference'];
    }
    else
    {
      $supplierReference = 0;
    }

    if ( $attributeInfo['price'] < 0 )
    {
      log::info("Attribute price is less than zero (".$attributeInfo['price'].")");
      //return false;
    }

    if ( !$update )
    {
      if ( !empty($reference) )
      {
        $attributeCombinationEntryID = $product->addCombinationEntity( 
          $attributeInfo['wholesale_price'], 
          $attributeInfo['price'], 
          0, // weight
          0, // unit_impact
          0, // ecotax
          $quantity, // quantity
          $attributeInfo['id_images'], 
          $reference, 
          $supplierReference, // supplier_reference
          0, // ean13
          0  // default
        );

        $retval = $product->addAttributeCombinaison( $attributeCombinationEntryID, $attributeCombinationList );
      }
      else
      {
        log::error("Could not set variant, missing reference (ext id: '". $product->external_id ."')");
      }
    }
    else
    {
      $product->updateProductAttribute(
        $existingID,
        $attributeInfo['wholesale_price'], 
        $attributeInfo['price'], 
        0, // weight
        0, // unit_impact
        0, // ecotax
        $quantity, // quantity
        $attributeInfo['id_images'], 
        $reference, 
        $supplierReference, // supplier_reference
        0, // ean13
        0,  // default
        NULL,
        NULL,
        1
      );
      $retval = $product->addAttributeCombinaison( $existingID, $attributeCombinationList );
    }

    //echo " > Attribute: price: ".$attributeInfo['price']." \n";

    $product->checkDefaultAttributes();

    return $retval;
  }

  /**
   * handleImages
   * 99% copied from admin/tabs/AdminImport.php
   * @return array List of images
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public static function handleImages( array $images, &$product, $defaults )
  {
    $imageIDs = array();

    $productHasImages = (bool)Image::getImages( $defaults['language'], $product->id);
    foreach ($images as $key => $url)
    {
      $image = null;
      if (!empty($url))
      {
        $image = new Image();
        $image->id_product = $product->id;
        $image->position = Image::getHighestPosition($product->id) + 1;
        $image->cover = (!$key AND !$productHasImages) ? true : false;
        $image->legend = array( $defaults['language'] => $product->name[ $defaults['language'] ] );

        if (
          (
            $fieldError = $image->validateFields(false, true)
          ) === true 
          AND 
          (
            $langFieldError = $image->validateFieldsLang(false, true)
          ) === true 
          AND 
          $image->save()
        )
        {
          if (!self::copyImg($product->id, $image->id, $url))
          {
            log::error("Copying image, id: ".$product->id.", image id: ". $image->id ." url: ".$url);
          }
        }
        else
        {
          $error = ($fieldError !== true ? $fieldError : '').($langFieldError !== true ? $langFieldError : '').mysql_error();
          log::error("Image add: ".$error);
        }
      }

      if ( !is_object($image) )
      {
        log::error("Image is non an object");
        continue;
      }
      $imageIDs[] = $image->id;
    }
    return $imageIDs;
  }

  /**
   * handleCustomizations
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public static function handleCustomizations( &$product, $customizations )
  {
    $existingCustomizations = $product->getCustomizationFields();

    $textFields = array();

    if ( is_array($existingCustomizations) )
    {
      foreach ($existingCustomizations as $typeID => $types) 
      {
        foreach ($types as $fieldID => $fields) 
        {
          foreach ($fields as $langID => $field) 
          {
            if ( ( $existingIndex = array_search($field['name'], $customizations) ) !== false )
            {
              unset($customizations[$existingIndex]);
            }
          }
        } 
      }
    }

    $product->customizable = 1;
    $product->text_fields = count($customizations);
    $product->save();
    $product->createLabels(0,count($customizations));

    $emptyIDs = array();

    // Now the should be more
    $existingCustomizations = $product->getCustomizationFields();
    foreach ($existingCustomizations as $typeID => $types) 
    {
      foreach ($types as $fieldID => $fields) 
      {
        foreach ($fields as $langID => $field) 
        {
          if ( empty($field['name']) )
          {
            $emptyIDs[] = $field['id_customization_field'];
          }
        }
      } 
    }

    $_POST['text_fields'] = count($customizations);

    foreach ($customizations as $field) 
    {
      $newID = array_shift($emptyIDs);
      // label + type + new id + language
      $_POST['label_'. _CUSTOMIZE_TEXTFIELD_ .'_'.$newID.'_7'] = $field['name'];
      if ( isset( $field['require'] ) && $field['require'] )
      {
        $_POST['require_'._CUSTOMIZE_TEXTFIELD_.'_'.$newID] = true;
      }
    }

    $product->updateLabels();
    $product->save();
  }
} // END class productWriter
