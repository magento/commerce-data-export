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
 * Video entity
 *
 * phpcs:disable Magento2.PHP.FinalImplementation
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Video
{
    /** @var \Magento\CatalogExportApi\Api\Data\MediaResource */
    private $preview;

    /** @var \Magento\CatalogExportApi\Api\Data\VideoItem */
    private $video;

    /** @var string */
    private $sortOrder;

    /**
     * Get preview
     *
     * @return \Magento\CatalogExportApi\Api\Data\MediaResource
     */
    public function getPreview(): ?MediaResource
    {
        return $this->preview;
    }

    /**
     * Set preview
     *
     * @param \Magento\CatalogExportApi\Api\Data\MediaResource $preview
     * @return void
     */
    public function setPreview(?MediaResource $preview): void
    {
        $this->preview = $preview;
    }

    /**
     * Get video
     *
     * @return \Magento\CatalogExportApi\Api\Data\VideoItem
     */
    public function getVideo(): ?VideoItem
    {
        return $this->video;
    }

    /**
     * Set video
     *
     * @param \Magento\CatalogExportApi\Api\Data\VideoItem $video
     * @return void
     */
    public function setVideo(?VideoItem $video): void
    {
        $this->video = $video;
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
