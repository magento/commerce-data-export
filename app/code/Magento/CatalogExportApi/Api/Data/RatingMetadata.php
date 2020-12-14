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
 * RatingMetadata entity
 *
 * phpcs:disable Magento2.PHP.FinalImplementation
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RatingMetadata
{
    /** @var string */
    private $ratingId;

    /** @var string */
    private $storeViewCode;

    /** @var string */
    private $name;

    /** @var \Magento\CatalogExportApi\Api\Data\RatingValue[]|null */
    private $values;

    /**
     * Get rating id
     *
     * @return string
     */
    public function getRatingId(): ?string
    {
        return $this->ratingId;
    }

    /**
     * Set rating id
     *
     * @param string $ratingId
     * @return void
     */
    public function setRatingId(?string $ratingId): void
    {
        $this->ratingId = $ratingId;
    }

    /**
     * Get store view code
     *
     * @return string
     */
    public function getStoreViewCode(): ?string
    {
        return $this->storeViewCode;
    }

    /**
     * Set store view code
     *
     * @param string $storeViewCode
     * @return void
     */
    public function setStoreViewCode(?string $storeViewCode): void
    {
        $this->storeViewCode = $storeViewCode;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return void
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get values
     *
     * @return \Magento\CatalogExportApi\Api\Data\RatingValue[]|null
     */
    public function getValues(): ?array
    {
        return $this->values;
    }

    /**
     * Set values
     *
     * @param \Magento\CatalogExportApi\Api\Data\RatingValue[] $values
     * @return void
     */
    public function setValues(?array $values = null): void
    {
        $this->values = $values;
    }
}
