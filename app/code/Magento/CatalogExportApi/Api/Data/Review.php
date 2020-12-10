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
 * Review entity
 *
 * phpcs:disable Magento2.PHP.FinalImplementation
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Review
{
    /** @var string */
    private $reviewId;

    /** @var int */
    private $productId;

    /** @var array */
    private $visibility;

    /** @var string */
    private $title;

    /** @var string */
    private $nickname;

    /** @var string */
    private $text;

    /** @var string */
    private $customerId;

    /** @var \Magento\CatalogExportApi\Api\Data\Rating[]|null */
    private $ratings;

    /**
     * Get review id
     *
     * @return string
     */
    public function getReviewId(): ?string
    {
        return $this->reviewId;
    }

    /**
     * Set review id
     *
     * @param string $reviewId
     * @return void
     */
    public function setReviewId(?string $reviewId): void
    {
        $this->reviewId = $reviewId;
    }

    /**
     * Get product id
     *
     * @return int
     */
    public function getProductId(): ?int
    {
        return $this->productId;
    }

    /**
     * Set product id
     *
     * @param int $productId
     * @return void
     */
    public function setProductId(?int $productId): void
    {
        $this->productId = $productId;
    }

    /**
     * Get visibility
     *
     * @return string[]
     */
    public function getVisibility(): ?array
    {
        return $this->visibility;
    }

    /**
     * Set visibility
     *
     * @param string[] $visibility
     * @return void
     */
    public function setVisibility(?array $visibility = null): void
    {
        $this->visibility = $visibility;
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
     * Get nickname
     *
     * @return string
     */
    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    /**
     * Set nickname
     *
     * @param string $nickname
     * @return void
     */
    public function setNickname(?string $nickname): void
    {
        $this->nickname = $nickname;
    }

    /**
     * Get text
     *
     * @return string
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * Set text
     *
     * @param string $text
     * @return void
     */
    public function setText(?string $text): void
    {
        $this->text = $text;
    }

    /**
     * Get customer id
     *
     * @return string
     */
    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    /**
     * Set customer id
     *
     * @param string $customerId
     * @return void
     */
    public function setCustomerId(?string $customerId): void
    {
        $this->customerId = $customerId;
    }

    /**
     * Get ratings
     *
     * @return \Magento\CatalogExportApi\Api\Data\Rating[]|null
     */
    public function getRatings(): ?array
    {
        return $this->ratings;
    }

    /**
     * Set ratings
     *
     * @param \Magento\CatalogExportApi\Api\Data\Rating[] $ratings
     * @return void
     */
    public function setRatings(?array $ratings = null): void
    {
        $this->ratings = $ratings;
    }
}
