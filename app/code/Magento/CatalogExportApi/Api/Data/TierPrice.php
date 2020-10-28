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
 * TierPrice entity
 *
 * phpcs:disable Magento2.PHP.FinalImplementation
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TierPrice
{
    /** @var float */
    private $qty;

    /** @var float */
    private $price;

    /**
     * Get qty
     *
     * @return float
     */
    public function getQty(): ?float
    {
        return $this->qty;
    }

    /**
     * Set qty
     *
     * @param float $qty
     * @return void
     */
    public function setQty(?float $qty): void
    {
        $this->qty = $qty;
    }

    /**
     * Get price
     *
     * @return float
     */
    public function getPrice(): ?float
    {
        return $this->price;
    }

    /**
     * Set price
     *
     * @param float $price
     * @return void
     */
    public function setPrice(?float $price): void
    {
        $this->price = $price;
    }
}
