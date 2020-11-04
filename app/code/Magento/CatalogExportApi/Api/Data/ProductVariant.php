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
 * ProductVariant entity
 *
 * phpcs:disable Magento2.PHP.FinalImplementation
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductVariant
{
    /** @var string */
    private $id;

    /** @var array */
    private $optionValues;

    /** @var string */
    private $productId;

    /**
     * Get id
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param string $id
     * @return void
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * Get option values
     *
     * @return string[]
     */
    public function getOptionValues(): ?array
    {
        return $this->optionValues;
    }

    /**
     * Set option values
     *
     * @param string[] $optionValues
     * @return void
     */
    public function setOptionValues(?array $optionValues = null): void
    {
        $this->optionValues = $optionValues;
    }

    /**
     * Get product id
     *
     * @return string
     */
    public function getProductId(): ?string
    {
        return $this->productId;
    }

    /**
     * Set product id
     *
     * @param string $productId
     * @return void
     */
    public function setProductId(?string $productId): void
    {
        $this->productId = $productId;
    }
}
