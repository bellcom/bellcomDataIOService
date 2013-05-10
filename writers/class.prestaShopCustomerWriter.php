<?php
namespace Bellcom;
use Customer;
use Address;
use Group;
use Exception;
use Tools;

/**
 * undocumented class
 *
 * @packaged bellcom
 * @author Henrik Farre <hf@bellcom.dk>
 **/
class prestaShopCustomerWriter extends prestaShopWriter
{
  static public function write(array $data)
  {
    $customerData   = $data['mappedData'];
    $rawData        = $data['data'];
    $defaults       = $data['defaults'];
    $mapping        = $data['mapping'];
    $params         = $data['params'];
    $postImport     = $data['postImport'];

    $fieldError     = false;
    $langFieldError = false;
    $customer       = false;
    $create         = false;

    $addressInfo    = array(
      'address_alias' => 'Min adresse',
      'address1'      => 'Ukendt',
      'address2'      => '',
      'postcode'      => '0000',
      'city'          => 'Ukendt',
      'country_id'    => 20,
    );

    $cleanGroups    = false; // Groups will be appended, if true all group relationss will be deletede first
    $groups         = array();

    if ( !isset( $customerData['external_id'] ) )
    {
      throw new Exception( 'External id must be set on customer!' );
    }

    $customer = Customer::getByExternalID( $customerData['external_id'] );

    if ( isset( $params['onlyUpdateObject'] ) && $params['onlyUpdateObject'] && $customer === false)
    {
      throw new Exception( 'onlyUpdateObject is set, but customer does not exists (external id: '. $customerData['external_id'] .')' );
    }

    if ( $customer === false ) // Can't use instanceOf, because it might be extended
    {
      $create   = true;
      $customer = new Customer();
    }

    foreach ( $customerData as $key => $value )
    {
      switch ($key) 
      {
        case 'name': // if first/lastname is not in seperate fields
          $posOfLastSpace = strrpos( trim( $value ), ' ' );
          $firstname = 'N';
          $lastname  = 'N';
          if ( $posOfLastSpace !== false )
          {
            $firstname = trim( substr( $value, 0, $posOfLastSpace ) );
            $lastname  = trim( substr( $value, $posOfLastSpace, strlen($value) ) );
          }

          if ( empty($firstname) || empty($lastname) )
          {
            throw new Exception('[ ERROR ] Customer name "'. $value .'" does not consist of first and lastname (external id: '. $customerData['external_id'] .'))');
          }

          $customer->firstname = $firstname;
          $customer->lastname = $lastname;
          break;
        case 'address_alias':
        case 'address1':
        case 'address2':
        case 'postcode':
        case 'city':
        case 'country_id': 
        case 'company': 
        case 'other': 
          if ( !empty($value) ) // don't override fallback address if empty
          {
            $addressInfo[$key] = $value;
          }
          break;
        case 'country':
          // TODO: look up country id
          break;
        case 'postcode_city': // same as name
          $posOfLastSpace = strpos( $value, ' ' );
          $postcode = '0000';
          $city     = 'Ingenby';
          if ( $posOfLastSpace !== false )
          {
            $postcode = trim( substr( $value, 0, $posOfLastSpace ) );
            $city     = trim( substr( $value, $posOfLastSpace, strlen($value) ) );
          }
          $addressInfo['postcode'] = $postcode;
          $addressInfo['city'] = $city;
          break;
        case 'passwd':
          $customer->passwd = Tools::encrypt($value);
          break;
        case 'group_id':
          $groups[] = $value; 
          break;
        case 'group_external_id':
          $group = Group::getByExternalID($value);
          if ( $group !== false )
          {
            $groups[] = $group->id; 
          }
          break;
        case 'group_default_id':
          $customer->id_default_group = $value;
          break;
        case 'group_clean':
          $cleanGroups = true;
          break;
        default:
          $customer->{$key} = $value;
          break;
      }
    }

    //print_r($customer);
    //return true;

    $saveStatus = $customer->save();

    if ( $saveStatus === false )
    {
      //echo "----------------[customer save failed]----------------\n";
      print_r($customer);
      return false;
    }

    // Updating customer, get first address and override 
    // NOTE: old orders will have their delivery/invoice address changed as orders just point to address ids
    // hf@bellcom.dk, 05-apr-2013: QBN-948-25765 -->>
    $address = false;
    if ( $create !== true )
    {
      $addressId = Address::getFirstCustomerAddressId( $customer->id );
      $address = new Address( $addressId );
    }

    if ( !is_object($address) ) 
    {
      $address = new Address();
    }

    $address->id_customer = $customer->id;
    $address->firstname   = $customer->firstname;
    $address->lastname    = $customer->lastname;
    $address->alias       = $addressInfo['address_alias'];
    $address->address1    = $addressInfo['address1'];
    if ( isset($addressInfo['address2']) && !empty($addressInfo['address2']) )
    {
      $address->address2    = $addressInfo['address2'];
    }
    if ( isset($addressInfo['company']) && !empty($addressInfo['company']) )
    {
      $address->company = $addressInfo['company'];
    }
    $address->id_country  = $addressInfo['country_id'];
    $address->city        = $addressInfo['city'];
    $address->postcode    = $addressInfo['postcode'];
	$address->dni = NULL;

    if ( isset($addressInfo['other']) && !empty($addressInfo['other']) )
    {
      $address->other = $addressInfo['other'];
    }

    if ( !$address->save() )
    {
      log::error( "Could not save address" );
    }
    // <<-- hf@bellcom.dk, 05-apr-2013: QBN-948-25765
    
    // hf@bellcom.dk, 05-apr-2013: clean groups -->>
    if ( $cleanGroups === true )
    {
      $customer->cleanGroups();
    } 
    // <<-- hf@bellcom.dk, 05-apr-2013: clean groups

    if ( sizeof($groups) > 0 )
    {
      //echo $customer->email.' '. implode(',',$groups) .PHP_EOL;
      $customer->addGroups($groups);
    }

    return true;
  }
} // END class customerWriter
