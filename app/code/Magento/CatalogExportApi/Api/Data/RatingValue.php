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
 * RatingValue entity
 *
 * phpcs:disable Magento2.PHP.FinalImplementation
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RatingValue
{
    /** @var string */
    private $valueId;

    /** @var string */
    private $value;

    /** @var int */
    private $position;

    /**
     * Get value id
     *
     * @return string
     */
    public function getValueId(): ?string
    {
        return $this->valueId;
    }

    /**
     * Set value id
     *
     * @param string $valueId
     * @return void
     */
    public function setValueId(?string $valueId): void
    {
        $this->valueId = $valueId;
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
     * Get position
     *
     * @return int
     */
    public function getPosition(): ?int
    {
        return $this->position;
    }

    /**
     * Set position
     *
     * @param int $position
     * @return void
     */
    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }
}
