<?php
require _PS_MODULE_DIR_.'/bellcomDataIOService/externalLibs/Pimple/Pimple.php';
require _PS_MODULE_DIR_.'/bellcomDataIOService/class.factory.php';
require _PS_MODULE_DIR_.'/bellcomDataIOService/class.importer.php';
require _PS_MODULE_DIR_.'/bellcomDataIOService/class.mapper.php';
require _PS_MODULE_DIR_.'/bellcomDataIOService/readers/class.reader.php';
require _PS_MODULE_DIR_.'/bellcomDataIOService/readers/class.readerCSV.php';
require _PS_MODULE_DIR_.'/bellcomDataIOService/class.validator.php';
require _PS_MODULE_DIR_.'/bellcomDataIOService/class.log.php';
require _PS_MODULE_DIR_.'/bellcomDataIOService/writers/class.writer.php';
require _PS_MODULE_DIR_.'/bellcomDataIOService/writers/class.prestaShopWriter.php';
require _PS_MODULE_DIR_.'/bellcomDataIOService/writers/class.prestaShopProductWriter.php';
require _PS_MODULE_DIR_.'/bellcomDataIOService/writers/class.prestaShopCustomerWriter.php';
require _PS_MODULE_DIR_.'/bellcomDataIOService/writers/class.prestaShopCategoryWriter.php';
require _PS_MODULE_DIR_.'/bellcomDataIOService/writers/class.prestaShopOrderSlipWriter.php';
require _PS_MODULE_DIR_.'/bellcomDataIOService/writers/class.writerCSV.php';
require _PS_MODULE_DIR_.'/bellcomDataIOService/fetchers/class.fetcher.php';
require _PS_MODULE_DIR_.'/bellcomDataIOService/fetchers/class.ftpTransport.php';
require _PS_MODULE_DIR_.'/bellcomDataIOService/processors/class.processor.php';
require _PS_MODULE_DIR_.'/bellcomDataIOService/processors/class.processorExtractor.php';
require _PS_MODULE_DIR_.'/bellcomDataIOService/processors/class.processorEncoding.php';
require _PS_MODULE_DIR_.'/bellcomDataIOService/processors/class.processorFixCategoryGroupPermissions.php';
require _PS_MODULE_DIR_.'/bellcomDataIOService/processors/class.processorCategoryNtree.php';
require _PS_MODULE_DIR_.'/bellcomDataIOService/processors/class.processorSearchIndexation.php';
require _PS_MODULE_DIR_.'/bellcomDataIOService/processors/class.processorCleanUp.php';
require _PS_MODULE_DIR_.'/bellcomDataIOService/processors/class.processorCheckFiles.php';
require _PS_MODULE_DIR_.'/bellcomDataIOService/processors/class.processorCleanMissingProducts.php';
require _PS_MODULE_DIR_.'/bellcomDataIOService/processors/class.processorDisableProductsWithWrongPrice.php';
require _PS_MODULE_DIR_.'/bellcomDataIOService/processors/class.processorMailStatus.php';
require _PS_MODULE_DIR_.'/bellcomDataIOService/class.exporter.php';

if ( is_file(_PS_MODULE_DIR_.'/bellcomDataIOService/helperFunctions.php') )
{
  require _PS_MODULE_DIR_.'/bellcomDataIOService/helperFunctions.php';
}
