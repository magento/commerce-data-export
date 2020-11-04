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
 * MediaItem entity
 *
 * phpcs:disable Magento2.PHP.FinalImplementation
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MediaItem
{
    /** @var string */
    private $url;

    /** @var string */
    private $label;

    /** @var array */
    private $types;

    /** @var int */
    private $sortOrder;

    /** @var \Magento\CatalogExportApi\Api\Data\VideoAttributes */
    private $videoAttributes;

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return void
     */
    public function setUrl(?string $url): void
    {
        $this->url = $url;
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
     * Get types
     *
     * @return string[]
     */
    public function getTypes(): ?array
    {
        return $this->types;
    }

    /**
     * Set types
     *
     * @param string[] $types
     * @return void
     */
    public function setTypes(?array $types = null): void
    {
        $this->types = $types;
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
     * Get video attributes
     *
     * @return \Magento\CatalogExportApi\Api\Data\VideoAttributes
     */
    public function getVideoAttributes(): ?VideoAttributes
    {
        return $this->videoAttributes;
    }

    /**
     * Set video attributes
     *
     * @param \Magento\CatalogExportApi\Api\Data\VideoAttributes $videoAttributes
     * @return void
     */
    public function setVideoAttributes(?VideoAttributes $videoAttributes): void
    {
        $this->videoAttributes = $videoAttributes;
    }
}
