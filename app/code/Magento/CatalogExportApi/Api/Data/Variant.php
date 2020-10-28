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
 * Variant entity
 *
 * phpcs:disable Magento2.PHP.FinalImplementation
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Variant
{
    /** @var string */
    private $sku;

    /** @var \Magento\CatalogExportApi\Api\Data\ProductPrice */
    private $minimumPrice;

    /** @var \Magento\CatalogExportApi\Api\Data\SingleValueAttribute[]|null */
    private $selections;

    /**
     * Get sku
     *
     * @return string
     */
    public function getSku(): ?string
    {
        return $this->sku;
    }

    /**
     * Set sku
     *
     * @param string $sku
     * @return void
     */
    public function setSku(?string $sku): void
    {
        $this->sku = $sku;
    }

    /**
     * Get minimum price
     *
     * @return \Magento\CatalogExportApi\Api\Data\ProductPrice
     */
    public function getMinimumPrice(): ?ProductPrice
    {
        return $this->minimumPrice;
    }

    /**
     * Set minimum price
     *
     * @param \Magento\CatalogExportApi\Api\Data\ProductPrice $minimumPrice
     * @return void
     */
    public function setMinimumPrice(?ProductPrice $minimumPrice): void
    {
        $this->minimumPrice = $minimumPrice;
    }

    /**
     * Get selections
     *
     * @return \Magento\CatalogExportApi\Api\Data\SingleValueAttribute[]|null
     */
    public function getSelections(): ?array
    {
        return $this->selections;
    }

    /**
     * Set selections
     *
     * @param \Magento\CatalogExportApi\Api\Data\SingleValueAttribute[] $selections
     * @return void
     */
    public function setSelections(?array $selections = null): void
    {
        $this->selections = $selections;
    }
}
