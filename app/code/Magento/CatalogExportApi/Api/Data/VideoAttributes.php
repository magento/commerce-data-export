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
 * VideoAttributes entity
 *
 * phpcs:disable Magento2.PHP.FinalImplementation
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class VideoAttributes
{
    /** @var string */
    private $mediaType;

    /** @var string */
    private $videoProvider;

    /** @var string */
    private $videoUrl;

    /** @var string */
    private $videoTitle;

    /** @var string */
    private $videoDescription;

    /** @var string */
    private $videoMetadata;

    /**
     * Get media type
     *
     * @return string
     */
    public function getMediaType(): ?string
    {
        return $this->mediaType;
    }

    /**
     * Set media type
     *
     * @param string $mediaType
     * @return void
     */
    public function setMediaType(?string $mediaType): void
    {
        $this->mediaType = $mediaType;
    }

    /**
     * Get video provider
     *
     * @return string
     */
    public function getVideoProvider(): ?string
    {
        return $this->videoProvider;
    }

    /**
     * Set video provider
     *
     * @param string $videoProvider
     * @return void
     */
    public function setVideoProvider(?string $videoProvider): void
    {
        $this->videoProvider = $videoProvider;
    }

    /**
     * Get video url
     *
     * @return string
     */
    public function getVideoUrl(): ?string
    {
        return $this->videoUrl;
    }

    /**
     * Set video url
     *
     * @param string $videoUrl
     * @return void
     */
    public function setVideoUrl(?string $videoUrl): void
    {
        $this->videoUrl = $videoUrl;
    }

    /**
     * Get video title
     *
     * @return string
     */
    public function getVideoTitle(): ?string
    {
        return $this->videoTitle;
    }

    /**
     * Set video title
     *
     * @param string $videoTitle
     * @return void
     */
    public function setVideoTitle(?string $videoTitle): void
    {
        $this->videoTitle = $videoTitle;
    }

    /**
     * Get video description
     *
     * @return string
     */
    public function getVideoDescription(): ?string
    {
        return $this->videoDescription;
    }

    /**
     * Set video description
     *
     * @param string $videoDescription
     * @return void
     */
    public function setVideoDescription(?string $videoDescription): void
    {
        $this->videoDescription = $videoDescription;
    }

    /**
     * Get video metadata
     *
     * @return string
     */
    public function getVideoMetadata(): ?string
    {
        return $this->videoMetadata;
    }

    /**
     * Set video metadata
     *
     * @param string $videoMetadata
     * @return void
     */
    public function setVideoMetadata(?string $videoMetadata): void
    {
        $this->videoMetadata = $videoMetadata;
    }
}
