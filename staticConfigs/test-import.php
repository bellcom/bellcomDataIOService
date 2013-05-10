<?php

use Bellcom\importer,
    Bellcom\processor,
    Bellcom\processorCleanMissingProducts,
    Bellcom\bellcomDataIOServiceEngine
    ;

$baseDir = '/var/www/prestashop.hf';

return array(
  'name' => 'Test import',
  'desc' => 'Tests',
  'steps' => array(

    'Import products' => array(
      'type' => bellcomDataIOServiceEngine::IMPORT,
      'config' => array(
        'products' => array(
          'files'  => array( $baseDir.'/product.csv' ),
          'reader' => 'readerCSV',
          'writer' => 'prestaShopProductWriter',
          'params' => array( 'delimiter' => ';', 'skipNumberOfLines' => 1 ),
          'mapping' => array( 
            'external_id'                  => array( 'field' => 0, 'validators' => array( 'ctype_digit' ) ),
            'reference'                    => array( 'field' => 1, 'validators' => array( 'ctype_digit' ) ),
            'name'                         => array( 'fields_combine' => array(2,3), 'transform' => array( 'replace_chars' => array('>' => '','<' => '') ), 'meta' => array( 'separator' => ', ') ),
            'description'                  => array( 'field' => 4, ),
            'price'                        => array( 'field' => 5, 'transform' => array( 'danish_number_to_float', 'remove_danish_tax' ), 'validators' => array( 'larger_than_zero' ) ),             
          ),
        ),
      ),
    ),

    'Import top categories' => array(
      'type' => bellcomDataIOServiceEngine::IMPORT,
      'config' => array(
        'categories' => array(
          'files'  => array( $baseDir.'/category.csv' ),
          'reader' => 'readerCSV',
          'writer' => 'prestaShopCategoryWriter', 
          'params' => array( 'delimiter' => ';', 'skipNumberOfLines' => 1 ),
          'mapping' => array( 
            'id_parent'       => array( 'field' => 0 ),
            'external_id'     => array( 'field' => 1, 'validators' => array('not_empty') ),
            'name'            => array( 'field' => 2, 'validators' => array('not_empty') ),
          ),
        ),
      ),
    ),

  ), // steps
);
