<?php
namespace Bellcom;
use OrderSlip;
use Order;
use Exception;
use Product;
use Customer;
use Module;
use Mail;

/**
 * undocumented class
 *
 * @packaged default
 * @author Henrik Farre <hf@bellcom.dk>
 **/
class prestaShopOrderSlipWriter extends prestaShopWriter
{
  /**
   * write
   * @return bool
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public static function write(array $data)
  {
    $orderData  = $data['mappedData'];
    $rawData    = $data['data'];
    $defaults   = $data['defaults'];
    $mapping    = $data['mapping'];
    $params     = $data['params'];
    $postImport = $data['postImport'];

    if ( !isset($orderData['id_order']) )
    {
      throw new Exception( 'Missing order id' );
    }

    $order = new Order( $orderData['id_order']);
    $products = array();
    $products = $order->getProducts();

    if ( $order->id === null )
    {
      throw new Exception( '[ ERROR ] Order with id "'.$orderData['id_order'].'" does not exist!' );
    }

    if ( !isset($orderData['id_customer']) )
    {
      $orderData['id_customer'] = $order->id_customer;
    }

    $customer = new Customer((int)$orderData['id_customer']);
    $params                = array();
	$params['{lastname}']  = $customer->lastname;
	$params['{firstname}'] = $customer->firstname;
	$params['{id_order}']  = $order->id;


    $creditAmount = 0;

    // Does a orderslip exist?
    $orderSlip = OrderSlip::getOrdersSlip( $orderData['id_customer'], $orderData['id_order'] );

    if ( is_array( $orderSlip ) && empty($orderSlip) )
    {
      $productList  = array();
      $qtyList      = array();
      $shippingCost = false;

      $pmap = array();
      foreach ($orderData['product_ids'] as $index => $extID) 
      {
        $found = false;
        $id    = false;
        foreach ($products as $product) 
        {
          if ( $product['product_reference'] == $extID )
          {
            $found = true;
            $id    = $product['product_id'];
            break;
          }
        }

        if ( $found === false )
        {
          throw new Exception( '[ ERROR ] OrderSlipWriter: Product with id "'.$extID.'" could not be found on order id ('.$order->id.')' );
        }

        $pmap[$index] = $id;
        $productList[$id] = $id;
      }

      foreach ($orderData['product_amounts'] as $index => $amount) 
      {
        $productId = $pmap[$index];
        $qtyList[$productId] = abs($amount);

        foreach ($products as $pid => $product) 
        {
          if ( $product['product_id'] == $productId )
          {
            $creditAmount += ( $product['product_price_wt'] * $qtyList[$productId]);
            continue;
          }
        }
      }

      if ( !OrderSlip::createOrderSlip( $order, $productList, $qtyList, $shippingCost ) )
      {
        echo "[ ERROR ] Failed to create an order slip\n";
      }
      else
      {
		Module::hookExec('orderSlip', array('order' => $order, 'productList' => $productList, 'qtyList' => $qtyList));
		Mail::Send((int)$order->id_lang, 'credit_slip', Mail::l('New credit slip regarding your order', $order->id_lang),
		$params, $customer->email, $customer->firstname.' '.$customer->lastname, NULL, NULL, NULL, NULL,
		_PS_MAIL_DIR_, true);
      }
    }
    else
    {
      // A order slip exists
      log::info("An order slip for this order (".$order->id.") exists!");
      return true;
    }
  }
} // END class prestaShop
