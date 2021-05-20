<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Store\Model\Store;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\UrlRewrite\Model\UrlRewrite;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

// Rewrites created with products
Resolver::getInstance()->requireDataFixture('Magento_CatalogDataExporter::Test/Integration/_files/setup_simple_products.php');
