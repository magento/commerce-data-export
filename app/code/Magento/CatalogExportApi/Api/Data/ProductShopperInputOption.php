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
 * ProductShopperInputOption entity
 *
 * phpcs:disable Magento2.PHP.FinalImplementation
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductShopperInputOption
{
    /** @var string */
    private $id;

    /** @var string */
    private $label;

    /** @var int */
    private $sortOrder;

    /** @var bool */
    private $required;

    /** @var string */
    private $renderType;

    /** @var \Magento\CatalogExportApi\Api\Data\Price[]|null */
    private $price;

    /** @var string */
    private $fileExtension;

    /** @var \Magento\CatalogExportApi\Api\Data\ValueRange */
    private $range;

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
     * Get required
     *
     * @return bool
     */
    public function getRequired(): ?bool
    {
        return $this->required;
    }

    /**
     * Set required
     *
     * @param bool $required
     * @return void
     */
    public function setRequired(?bool $required): void
    {
        $this->required = $required;
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
     * Get price
     *
     * @return \Magento\CatalogExportApi\Api\Data\Price[]|null
     */
    public function getPrice(): ?array
    {
        return $this->price;
    }

    /**
     * Set price
     *
     * @param \Magento\CatalogExportApi\Api\Data\Price[] $price
     * @return void
     */
    public function setPrice(?array $price = null): void
    {
        $this->price = $price;
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
     * Get range
     *
     * @return \Magento\CatalogExportApi\Api\Data\ValueRange
     */
    public function getRange(): ?ValueRange
    {
        return $this->range;
    }

    /**
     * Set range
     *
     * @param \Magento\CatalogExportApi\Api\Data\ValueRange $range
     * @return void
     */
    public function setRange(?ValueRange $range): void
    {
        $this->range = $range;
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
