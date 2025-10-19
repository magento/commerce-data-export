<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */

use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Catalog/_files/product_simple.php');
Resolver::getInstance()->requireDataFixture('Magento/Sales/_files/order_rollback.php');
