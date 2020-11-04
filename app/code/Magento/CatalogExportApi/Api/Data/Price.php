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
 * Price entity
 *
 * phpcs:disable Magento2.PHP.FinalImplementation
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Price
{
    /** @var float */
    private $regularPrice;

    /** @var float */
    private $finalPrice;

    /** @var string */
    private $scope;

    /**
     * Get regular price
     *
     * @return float
     */
    public function getRegularPrice(): ?float
    {
        return $this->regularPrice;
    }

    /**
     * Set regular price
     *
     * @param float $regularPrice
     * @return void
     */
    public function setRegularPrice(?float $regularPrice): void
    {
        $this->regularPrice = $regularPrice;
    }

    /**
     * Get final price
     *
     * @return float
     */
    public function getFinalPrice(): ?float
    {
        return $this->finalPrice;
    }

    /**
     * Set final price
     *
     * @param float $finalPrice
     * @return void
     */
    public function setFinalPrice(?float $finalPrice): void
    {
        $this->finalPrice = $finalPrice;
    }

    /**
     * Get scope
     *
     * @return string
     */
    public function getScope(): ?string
    {
        return $this->scope;
    }

    /**
     * Set scope
     *
     * @param string $scope
     * @return void
     */
    public function setScope(?string $scope): void
    {
        $this->scope = $scope;
    }
}
