<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 *
 * Generated from et_schema.xml. DO NOT EDIT!
 */

declare(strict_types=1);

namespace Magento\CatalogExportApi\Api\Data;

/**
 * Inventory entity
 *
 * phpcs:disable Magento2.PHP.FinalImplementation
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Inventory
{
    /** @var int */
    private $qty;

    /** @var \Magento\CatalogExportApi\Api\Data\InventorySettings */
    private $configuration;

    /**
     * Get qty
     *
     * @return int
     */
    public function getQty(): ?int
    {
        return $this->qty;
    }

    /**
     * Set qty
     *
     * @param int $qty
     * @return void
     */
    public function setQty(?int $qty): void
    {
        $this->qty = $qty;
    }

    /**
     * Get configuration
     *
     * @return \Magento\CatalogExportApi\Api\Data\InventorySettings
     */
    public function getConfiguration(): ?InventorySettings
    {
        return $this->configuration;
    }

    /**
     * Set configuration
     *
     * @param \Magento\CatalogExportApi\Api\Data\InventorySettings $configuration
     * @return void
     */
    public function setConfiguration(?InventorySettings $configuration): void
    {
        $this->configuration = $configuration;
    }
}
