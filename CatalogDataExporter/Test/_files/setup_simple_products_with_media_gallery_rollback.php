<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */

use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento_CatalogDataExporter::Test/_files/setup_simple_products_rollback.php');
