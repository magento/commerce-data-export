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
 * ProductOption entity
 *
 * phpcs:disable Magento2.PHP.FinalImplementation
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductOption
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

    /** @var string */
    private $type;

    /** @var \Magento\CatalogExportApi\Api\Data\ProductOptionValue[]|null */
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
     * Get values
     *
     * @return \Magento\CatalogExportApi\Api\Data\ProductOptionValue[]|null
     */
    public function getValues(): ?array
    {
        return $this->values;
    }

    /**
     * Set values
     *
     * @param \Magento\CatalogExportApi\Api\Data\ProductOptionValue[] $values
     * @return void
     */
    public function setValues(?array $values = null): void
    {
        $this->values = $values;
    }
}
