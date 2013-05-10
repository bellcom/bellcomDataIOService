<?php

namespace Bellcom;
use Configuration;
use SimpleXmlElement;

class quickpay
{
  /**
   * quickpayApiStatus based on code from Prestashop quickpay module
   * Requires that the quickpay module is installed and configured
   *
   * @param int $cartID
   * @return xml object 
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public static function status( $cartID )
  {
    $cartID = str_pad( $cartID, 5, 0, STR_PAD_LEFT );

    $merchant = Configuration::get('_QUICKPAY_MERCHANTID');
    $md5secret = Configuration::get('_QUICKPAY_MD5');
    $protocol = 4;

    $md5check = md5($protocol.'status'.$merchant.$cartID.$md5secret);
    $ch = curl_init();

    $fields = 'protocol='.$protocol.'&ordernumber='.$cartID.'&protocol='.$protocol.'&msgtype=status&merchant='.$merchant.'&md5check='.$md5check;

    // set URL and other appropriate options
    curl_setopt($ch, CURLOPT_URL, "https://secure.quickpay.dk/api");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

    // grab URL and pass it to the browser
    $data = curl_exec($ch);

    // close cURL resource, and free up system resourc
    curl_close($ch);
    $xml = new SimpleXmlElement($data);

    return $xml;
  }
}
