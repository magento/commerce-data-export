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
declare(strict_types=1);

use Magento\Catalog\Observer\SwitchPriceAttributeScopeOnConfigChange;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Store\Model\Store;
use Magento\Framework\Event\Observer;

$objectManager = Bootstrap::getObjectManager();

// We have to set config value, as fixture setting doesn't work
/** @var ReinitableConfigInterface $reinitiableConfig */
$reinitiableConfig = $objectManager->get(ReinitableConfigInterface::class);
$reinitiableConfig->setValue(
    'catalog/price/scope',
    Store::PRICE_SCOPE_WEBSITE
);
$observer = $objectManager->get(Observer::class);
$objectManager->get(SwitchPriceAttributeScopeOnConfigChange::class)
    ->execute($observer);
