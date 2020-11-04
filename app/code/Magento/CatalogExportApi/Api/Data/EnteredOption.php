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
 * EnteredOption entity
 *
 * phpcs:disable Magento2.PHP.FinalImplementation
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EnteredOption
{
    /** @var string */
    private $id;

    /** @var string */
    private $value;

    /** @var bool */
    private $isRequired;

    /** @var int */
    private $sortOrder;

    /** @var string */
    private $type;

    /** @var string */
    private $renderType;

    /** @var string */
    private $productSku;

    /** @var string */
    private $sku;

    /** @var \Magento\CatalogExportApi\Api\Data\ProductPrice */
    private $price;

    /** @var string */
    private $priceType;

    /** @var int */
    private $maxCharacters;

    /** @var string */
    private $fileExtension;

    /** @var int */
    private $imageSizeX;

    /** @var int */
    private $imageSizeY;

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
     * Get value
     *
     * @return string
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return void
     */
    public function setValue(?string $value): void
    {
        $this->value = $value;
    }

    /**
     * Get is required
     *
     * @return bool
     */
    public function getIsRequired(): ?bool
    {
        return $this->isRequired;
    }

    /**
     * Set is required
     *
     * @param bool $isRequired
     * @return void
     */
    public function setIsRequired(?bool $isRequired): void
    {
        $this->isRequired = $isRequired;
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
     * Get type
     *
     * @return string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return void
     */
    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    /**
     * Get render type
     *
     * @return string
     */
    public function getRenderType(): ?string
    {
        return $this->renderType;
    }

    /**
     * Set render type
     *
     * @param string $renderType
     * @return void
     */
    public function setRenderType(?string $renderType): void
    {
        $this->renderType = $renderType;
    }

    /**
     * Get product sku
     *
     * @return string
     */
    public function getProductSku(): ?string
    {
        return $this->productSku;
    }

    /**
     * Set product sku
     *
     * @param string $productSku
     * @return void
     */
    public function setProductSku(?string $productSku): void
    {
        $this->productSku = $productSku;
    }

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
     * Get price
     *
     * @return \Magento\CatalogExportApi\Api\Data\ProductPrice
     */
    public function getPrice(): ?ProductPrice
    {
        return $this->price;
    }

    /**
     * Set price
     *
     * @param \Magento\CatalogExportApi\Api\Data\ProductPrice $price
     * @return void
     */
    public function setPrice(?ProductPrice $price): void
    {
        $this->price = $price;
    }

    /**
     * Get price type
     *
     * @return string
     */
    public function getPriceType(): ?string
    {
        return $this->priceType;
    }

    /**
     * Set price type
     *
     * @param string $priceType
     * @return void
     */
    public function setPriceType(?string $priceType): void
    {
        $this->priceType = $priceType;
    }

    /**
     * Get max characters
     *
     * @return int
     */
    public function getMaxCharacters(): ?int
    {
        return $this->maxCharacters;
    }

    /**
     * Set max characters
     *
     * @param int $maxCharacters
     * @return void
     */
    public function setMaxCharacters(?int $maxCharacters): void
    {
        $this->maxCharacters = $maxCharacters;
    }

    /**
     * Get file extension
     *
     * @return string
     */
    public function getFileExtension(): ?string
    {
        return $this->fileExtension;
    }

    /**
     * Set file extension
     *
     * @param string $fileExtension
     * @return void
     */
    public function setFileExtension(?string $fileExtension): void
    {
        $this->fileExtension = $fileExtension;
    }

    /**
     * Get image size x
     *
     * @return int
     */
    public function getImageSizeX(): ?int
    {
        return $this->imageSizeX;
    }

    /**
     * Set image size x
     *
     * @param int $imageSizeX
     * @return void
     */
    public function setImageSizeX(?int $imageSizeX): void
    {
        $this->imageSizeX = $imageSizeX;
    }

    /**
     * Get image size y
     *
     * @return int
     */
    public function getImageSizeY(): ?int
    {
        return $this->imageSizeY;
    }

    /**
     * Set image size y
     *
     * @param int $imageSizeY
     * @return void
     */
    public function setImageSizeY(?int $imageSizeY): void
    {
        $this->imageSizeY = $imageSizeY;
    }
}
