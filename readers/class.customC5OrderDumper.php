<?php

namespace Bellcom;
use Pimple;
use Order;
use Customer;
use Address;
use Country;
use Currency;
use Product;
use Configuration;
use SimpleXmlElement;
use Carrier;
use Db;
use Exception;

/**
 * undocumented class
 *
 * @packaged bellcom
 * @author Henrik Farre <hf@bellcom.dk>
 **/
class customC5OrderDumper extends reader
{
  private $app = null;
  private $data = array();
  private $orderID = null;

  const STATUS_EXPORTED = 'exported';
  const STATUS_FAILED = 'failed';

  public function __construct(Pimple $app)
  {
    $this->app = $app;
  }

  /**
   * getOrdersWithExternalStatus
   * TODO: show be on order override class
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public function getOrdersWithExternalStatus( $status = null )
  {
    $sql = "SELECT id_order FROM `"._DB_PREFIX_	."orders` WHERE external_status ".( is_null( $status ) ? 'IS NULL' : "= '".$status."'" );
    $result = Db::getInstance()->ExecuteS( $sql );
    return $result;
  }

  /**
   * setOrderExternalStatus
   * TODO: show be on order override class
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public function setOrderExternalStatus( $orderID, $status )
  {
    $sql = "UPDATE `"._DB_PREFIX_	."orders` SET external_status = '".$status."' WHERE id_order = ". (int) $orderID;
    Db::getInstance()->ExecuteS($sql);
  }

  public function open( $orderID = null )
  {
    // First set this to the correct orderID when everything went ok
    $this->orderID = null;

    if ( is_null($orderID) )
    {
      throw new Exception('Missing orderID');
    }

    $order           = new Order( $orderID );

    // In order to handle bankwire (faktura) as payment, which has to be set manully to valid,
    // all orders are fetched, but only those that match the following are exported
    if ( $order->module == 'quickpay' && !$order->valid )
    {
      return false;
    }

    if ( $order->module == 'bellcomPrepaid' && !$order->valid )
    {
      return false;
    }

    if ( $order->module == 'bankwire' && !$order->valid )
    {
      $order->valid = true;
      $sql = "UPDATE `"._DB_PREFIX_	."orders` SET valid = 1 WHERE id_order = ". (int) $orderID;
      Db::getInstance()->ExecuteS( $sql );
    }

    $customer        = new Customer( $order->id_customer );

    if ( is_null( $customer->id ) )
    {
      return false;
    }

    $deliveryAddress = new Address( $order->id_address_delivery );
    $invoiceAddress  = new Address( $order->id_address_invoice );
    $currency        = new Currency( $order->id_currency );
    $products        = $order->getProducts();
    $carrier         = new Carrier( $order->id_carrier );

    $totalProductPriceExVat = 0;
    $totalVatAmount = 0;

    // moms beregnes af brutto prisen
    foreach ($products as $product) 
    {
      $totalProductPriceExVat += ($product['product_price_wt'] - (( $product['product_price_wt'] / ($product['tax_rate']+100) ) * $product['tax_rate'])) * $product['product_quantity']; 
    }
    // Add shipping
    $totalProductPriceExVat += $order->total_shipping;
    $totalVatAmount = $order->total_paid - $totalProductPriceExVat;

    $group   = 'detail';
    $account = '';

    $accounts = \bellcomAccount::getAccountsForCustomer($customer->id);
    if ( !empty($accounts) && isset($accounts[0]['external_id']) )
    {
      $account = $accounts[0]['external_id'];
      $data = unserialize($accounts[0]['data']);
      $group = $data[4];
    }

    $type = 1; // Customer
    $fields = array(
      'Type'         => $type,
      'Ordre ID'     => $orderID,
      'Konto'        => $account,
      'Navn'         => $invoiceAddress->firstname.' '.$invoiceAddress->lastname, 
      'Firma'        => $invoiceAddress->company,                                  
      'Adresse1'     => $invoiceAddress->address1,                                 
      'Adresse2'     => $invoiceAddress->address2,                                 
      'PostBy'       => $invoiceAddress->postcode.' '.$invoiceAddress->city,      
      'Land'         => $invoiceAddress->country,                                  
      'Telefon'      => $invoiceAddress->phone,
      'Email'        => $customer->email,
      'Levering1'    => $deliveryAddress->firstname.' '.$deliveryAddress->lastname,
      'Levering2'    => $deliveryAddress->company,                                 
      'Levering3'    => $deliveryAddress->address1,                                
      'Levering4'    => $deliveryAddress->address2,                                
      'Levering5'    => $deliveryAddress->postcode.' '.$deliveryAddress->city,     
      'LevLand'      => $deliveryAddress->country,                                 
      'Gruppe'       => $group,
      'Oprettet'     => $order->date_add,
      'Leveres'      => '',
      'Valuta'       => $currency->iso_code,
      'Varebeløb'    => number_format( $totalProductPriceExVat, 2, ",","" ),
      'Momsbeløb'    => number_format( $totalVatAmount, 2, ",","" ),
      'Fakturatotal' => number_format( $order->total_paid, 2, ",","" ),
    );

    $this->data[] = $fields;

    $lineNumber = 0;

    $type = 2; // Product
    foreach ($products as $product) 
    {
      //$extID = Product::getExternalIDByID( $product['product_id'] );
      $fields = array(
        'Type'                   => $type,
        'Ordre ID'               => $orderID,
        'Linie nummer'           => $lineNumber,
        'C5 produkt id'          => $product['product_reference'],
        'Produkt navn'           => str_replace( array('Størrelse','Farve'), array('',''), $product['product_name'] ),
        'Mængde'                 => $product['product_quantity'],
        'Pris pr stk'            => number_format( $product['product_price'], 2, ",","" ), // eksl. moms, uden rabat
        'Rabat'                  => number_format( $product['reduction_amount'], 2, ",","" ), // af beløb m. moms
        'Total pris efter rabat' => number_format( $product['total_wt'], 2, ",","" ), // total m. rabat m. moms
      );

      if ( $currency->iso_code == 'PON' )
      {
        $fields['Pris pr stk'] = number_format( ( $product['product_price'] * 1.25 ), 2, ",","" ); // incl. moms når det er points 
      }

      $this->data[] = $fields;
      $lineNumber++;
    }

    // Fragt
    $extID = '10';
    $fields = array(
      'Type' => $type,
      'Ordre ID'         => $orderID,
      'Linie nummer'     => $lineNumber,
      'C5 produkt id'    => $extID,
      'Produkt navn'     => $carrier->name,
      'Mængde'           => 1,
      'Pris'             => number_format( $order->total_shipping, 2, ",","" ), // eksl. moms, uden rabat
      'Rabat'            => 0, // af beløb m. moms
      'Pris efter rabat' => number_format( $order->total_shipping, 2, ",","" ), // total m. rabat m. moms
    );

    $this->data[] = $fields;

    $paymentType   = $order->payment;
    $transActionID = '';

    if ( $order->payment == 'quickpay' )
    {
      $response = quickpay::status( $order->id_cart );

      if ( $response->qpstat != '000' )
      {
        // TODO: handle error
      }

      $paymentType   = (string) $response->cardtype;
      $transActionID = (string) $response->transaction;
    }

    if ( $order->module == 'bankwire' )
    {
      $paymentType = 'faktura';
    }

    $type = 3; // Payment
    $fields = array(
      'Type'               => $type,
      'Betalingsmiddel'    => $paymentType,
      'Beløb'              => number_format( $order->total_paid, 2, ",","" ),
      'Valutakode'         => $currency->iso_code,
      'Transaktionsnummer' => $transActionID,
    );

    $this->data[] = $fields;
    // Don't set this if there is an error:
    $this->orderID = $orderID;
  }

  public function __destruct()
  {
    $this->close();
  }

  public function close()
  {
    if ( !is_null( $this->orderID ) )
    {
      $this->setOrderExternalStatus( $this->orderID, self::STATUS_EXPORTED );
      $this->orderID = null;
    }
  }

  public function getData()
  {
    return array_shift($this->data);
  }
}
