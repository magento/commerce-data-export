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
 * InventorySettings entity
 *
 * phpcs:disable Magento2.PHP.FinalImplementation
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InventorySettings
{
    /** @var bool */
    private $status;

    /** @var bool */
    private $manageStock;

    /** @var float */
    private $threshold;

    /** @var bool */
    private $productAvailable;

    /**
     * Get status
     *
     * @return bool
     */
    public function getStatus(): ?bool
    {
        return $this->status;
    }

    /**
     * Set status
     *
     * @param bool $status
     * @return void
     */
    public function setStatus(?bool $status): void
    {
        $this->status = $status;
    }

    /**
     * Get manage stock
     *
     * @return bool
     */
    public function getManageStock(): ?bool
    {
        return $this->manageStock;
    }

    /**
     * Set manage stock
     *
     * @param bool $manageStock
     * @return void
     */
    public function setManageStock(?bool $manageStock): void
    {
        $this->manageStock = $manageStock;
    }

    /**
     * Get threshold
     *
     * @return float
     */
    public function getThreshold(): ?float
    {
        return $this->threshold;
    }

    /**
     * Set threshold
     *
     * @param float $threshold
     * @return void
     */
    public function setThreshold(?float $threshold): void
    {
        $this->threshold = $threshold;
    }

    /**
     * Get product available
     *
     * @return bool
     */
    public function getProductAvailable(): ?bool
    {
        return $this->productAvailable;
    }

    /**
     * Set product available
     *
     * @param bool $productAvailable
     * @return void
     */
    public function setProductAvailable(?bool $productAvailable): void
    {
        $this->productAvailable = $productAvailable;
    }
}
