<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */

use Magento\Store\Model\Store;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\UrlRewrite\Model\UrlRewrite;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

// Rewrites created with products
Resolver::getInstance()->requireDataFixture('Magento_CatalogDataExporter::Test/_files/setup_simple_products.php');
