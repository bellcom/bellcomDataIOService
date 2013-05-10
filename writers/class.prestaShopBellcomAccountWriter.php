<?php
namespace Bellcom;
use Exception;
use bellcomAccount;

/**
 * undocumented class
 *
 * @packaged bellcom
 * @author Henrik Farre <hf@bellcom.dk>
 **/
class prestaShopBellcomAccountWriter extends prestaShopWriter
{
  static public function write(array $data)
  {
    if ( !class_exists('bellcomAccount') )
    {
      throw new Exception('class bellcomAccount not found');
    }

    $customerData   = $data['mappedData'];
    $rawData        = $data['data'];
    $defaults       = $data['defaults'];
    $mapping        = $data['mapping'];
    $params         = $data['params'];
    $postImport     = $data['postImport'];

    $accountInfo    = array();

    foreach ( $customerData as $key => $value )
    {
      switch ($key) 
      {
        default:
          $accountInfo[$key] = $value;
          break;
      }
    }

    if ( sizeof($accountInfo) > 0 )
    {
      if ( isset($accountInfo['external_id']) )
      {
        $account = bellcomAccount::getAccountByExternalID( $accountInfo['external_id'] );
        $accountID = isset( $account['id_bc_account'] ) ? $account['id_bc_account'] : false;
  
        if ( $accountID === false )
        {
          $accountID = bellcomAccount::add( $accountInfo['external_id'], $accountInfo['name'], $accountInfo['data'] );
        }
        else
        {
          $accountID = bellcomAccount::update( $accountInfo['external_id'], $accountInfo['name'], $accountInfo['data'] );
        }
      }

      if ( isset($accountInfo['role_name']))
      {
        $roleID = bellcomAccount::getRoleIDByName( $accountInfo['role_name'] );
        if ( $roleID === false )
        {
          $roleID = bellcomAccount::addRole( $accountInfo['role_name'] );
        }
      }
    }

    return true;
  }
}
