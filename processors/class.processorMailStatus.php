<?php

namespace Bellcom;
use Pimple;
use Exception;
use Configuration;

/**
 * undocumented class
 *
 * @packaged default
 * @author Henrik Farre <hf@bellcom.dk>
 **/
class processorMailStatus
{
  private $app = null;

  /**
   * undocumented function
   *
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public function __construct(Pimple $app)
  {
    $this->app = $app;
  }

  /**
   * undocumented function
   *
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public function process()
  {
    if ( $this->app['error_counter'] === 0 ) 
    {
      return;
    }

    $fromMail = Configuration::get('PS_SHOP_EMAIL');
    $fromName = 'Import';

    $subject = 'Status mail fra import';
    $subject = '=?utf-8?B?'.base64_encode($subject).'?=';

    $message = '
      <html>
        <head>
          <title>Status mail fra import</title>
        </head>
        <body>
          <ul>';

    foreach ($this->app['error_list'] as $error) 
    {
      $message .= '<li>'.$error.'</li>';
    }

    $message .='
          </ul>
        </body>
      </html>';

    $headers = array();
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=UTF-8';

    $recipients = $this->app['processConfig']['params']['recipients'];

    $to = array_shift($recipients);
    $headers[] = 'From: '. $fromName .' <'. $fromMail .'>';

    while( !empty($recipients) )
    {
      $cc = array_shift( $recipients );
      $headers[] = 'Cc: '. $cc;
    }

    $headersString = implode("\r\n",$headers);

    mail($to, $subject, $message, $headersString, '-f'.$fromMail);
  }
}
