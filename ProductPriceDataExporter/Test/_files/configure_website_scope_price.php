<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
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
