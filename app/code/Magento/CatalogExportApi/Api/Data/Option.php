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
 * Option entity
 *
 * phpcs:disable Magento2.PHP.FinalImplementation
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Option
{
    /** @var string */
    private $id;

    /** @var string */
    private $type;

    /** @var string */
    private $attributeId;

    /** @var string */
    private $attributeCode;

    /** @var bool */
    private $useDefault;

    /** @var string */
    private $renderType;

    /** @var bool */
    private $isRequired;

    /** @var string */
    private $title;

    /** @var int */
    private $sortOrder;

    /** @var string */
    private $productSku;

    /** @var \Magento\CatalogExportApi\Api\Data\OptionValue[]|null */
    private $values;

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
     * Get attribute id
     *
     * @return string
     */
    public function getAttributeId(): ?string
    {
        return $this->attributeId;
    }

    /**
     * Set attribute id
     *
     * @param string $attributeId
     * @return void
     */
    public function setAttributeId(?string $attributeId): void
    {
        $this->attributeId = $attributeId;
    }

    /**
     * Get attribute code
     *
     * @return string
     */
    public function getAttributeCode(): ?string
    {
        return $this->attributeCode;
    }

    /**
     * Set attribute code
     *
     * @param string $attributeCode
     * @return void
     */
    public function setAttributeCode(?string $attributeCode): void
    {
        $this->attributeCode = $attributeCode;
    }

    /**
     * Get use default
     *
     * @return bool
     */
    public function getUseDefault(): ?bool
    {
        return $this->useDefault;
    }

    /**
     * Set use default
     *
     * @param bool $useDefault
     * @return void
     */
    public function setUseDefault(?bool $useDefault): void
    {
        $this->useDefault = $useDefault;
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
     * Get title
     *
     * @return string
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return void
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
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
     * Get values
     *
     * @return \Magento\CatalogExportApi\Api\Data\OptionValue[]|null
     */
    public function getValues(): ?array
    {
        return $this->values;
    }

    /**
     * Set values
     *
     * @param \Magento\CatalogExportApi\Api\Data\OptionValue[] $values
     * @return void
     */
    public function setValues(?array $values = null): void
    {
        $this->values = $values;
    }
}
