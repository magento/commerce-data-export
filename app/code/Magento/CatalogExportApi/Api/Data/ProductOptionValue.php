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
 * ProductOptionValue entity
 *
 * phpcs:disable Magento2.PHP.FinalImplementation
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductOptionValue
{
    /** @var string */
    private $id;

    /** @var string */
    private $label;

    /** @var int */
    private $sortOrder;

    /** @var bool */
    private $default;

    /** @var string */
    private $imageUrl;

    /** @var bool */
    private $qtyMutability;

    /** @var float */
    private $qty;

    /** @var string */
    private $infoUrl;

    /** @var float */
    private $price;

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
     * Get label
     *
     * @return string
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * Set label
     *
     * @param string $label
     * @return void
     */
    public function setLabel(?string $label): void
    {
        $this->label = $label;
    }

    /**
     * Get sort order
     *
     * @return int
     */
    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    /**
     * Set sort order
     *
     * @param int $sortOrder
     * @return void
     */
    public function setSortOrder(?int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    /**
     * Get default
     *
     * @return bool
     */
    public function getDefault(): ?bool
    {
        return $this->default;
    }

    /**
     * Set default
     *
     * @param bool $default
     * @return void
     */
    public function setDefault(?bool $default): void
    {
        $this->default = $default;
    }

    /**
     * Get image url
     *
     * @return string
     */
    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    /**
     * Set image url
     *
     * @param string $imageUrl
     * @return void
     */
    public function setImageUrl(?string $imageUrl): void
    {
        $this->imageUrl = $imageUrl;
    }

    /**
     * Get qty mutability
     *
     * @return bool
     */
    public function getQtyMutability(): ?bool
    {
        return $this->qtyMutability;
    }

    /**
     * Set qty mutability
     *
     * @param bool $qtyMutability
     * @return void
     */
    public function setQtyMutability(?bool $qtyMutability): void
    {
        $this->qtyMutability = $qtyMutability;
    }

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
     * Get info url
     *
     * @return string
     */
    public function getInfoUrl(): ?string
    {
        return $this->infoUrl;
    }

    /**
     * Set info url
     *
     * @param string $infoUrl
     * @return void
     */
    public function setInfoUrl(?string $infoUrl): void
    {
        $this->infoUrl = $infoUrl;
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
