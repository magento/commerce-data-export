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

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Create fixture categories
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/** @var ObjectManagerInterface $objectManager */
$objectManager = Bootstrap::getObjectManager();

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var @var Magento\Catalog\Api\CategoryRepositoryInterface $categoryInterface */
$categoryInterface = $objectManager->create(Magento\Catalog\Api\CategoryRepositoryInterface::class);

try {
    $category = $categoryInterface->get(200);
    if ($category->getId()) {
        $categoryInterface->delete($category);
    }

    $category = $categoryInterface->get(100);
    if ($category->getId()) {
        $categoryInterface->delete($category);
    }
} catch (Exception $e) {
    // Nothing to delete
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
