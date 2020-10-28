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
 * Image entity
 *
 * phpcs:disable Magento2.PHP.FinalImplementation
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Image
{
    /** @var \Magento\CatalogExportApi\Api\Data\MediaResource */
    private $resource;

    /** @var string */
    private $sortOrder;

    /**
     * Get resource
     *
     * @return \Magento\CatalogExportApi\Api\Data\MediaResource
     */
    public function getResource(): ?MediaResource
    {
        return $this->resource;
    }

    /**
     * Set resource
     *
     * @param \Magento\CatalogExportApi\Api\Data\MediaResource $resource
     * @return void
     */
    public function setResource(?MediaResource $resource): void
    {
        $this->resource = $resource;
    }

    /**
     * Get sort order
     *
     * @return string
     */
    public function getSortOrder(): ?string
    {
        return $this->sortOrder;
    }

    /**
     * Set sort order
     *
     * @param string $sortOrder
     * @return void
     */
    public function setSortOrder(?string $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }
}
