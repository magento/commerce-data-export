<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */

use Magento\Store\Model\Store;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\UrlRewrite\Model\UrlRewrite;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

// Rewrites created with products
Resolver::getInstance()->requireDataFixture('Magento_CatalogDataExporter::Test/_files/setup_simple_products.php');
