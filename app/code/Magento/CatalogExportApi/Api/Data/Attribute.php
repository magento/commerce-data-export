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
 * Attribute entity
 *
 * phpcs:disable Magento2.PHP.FinalImplementation
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Attribute
{
    /** @var string */
    private $attributeCode;

    /** @var string */
    private $type;

    /** @var array */
    private $value;

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
     * Get value
     *
     * @return string[]
     */
    public function getValue(): ?array
    {
        return $this->value;
    }

    /**
     * Set value
     *
     * @param string[] $value
     * @return void
     */
    public function setValue(?array $value = null): void
    {
        $this->value = $value;
    }
}
