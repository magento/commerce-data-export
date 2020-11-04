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
 * Breadcrumbs entity
 *
 * phpcs:disable Magento2.PHP.FinalImplementation
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Breadcrumbs
{
    /** @var string */
    private $categoryId;

    /** @var string */
    private $categoryName;

    /** @var int */
    private $categoryLevel;

    /** @var string */
    private $categoryUrlKey;

    /** @var string */
    private $categoryUrlPath;

    /**
     * Get category id
     *
     * @return string
     */
    public function getCategoryId(): ?string
    {
        return $this->categoryId;
    }

    /**
     * Set category id
     *
     * @param string $categoryId
     * @return void
     */
    public function setCategoryId(?string $categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    /**
     * Get category name
     *
     * @return string
     */
    public function getCategoryName(): ?string
    {
        return $this->categoryName;
    }

    /**
     * Set category name
     *
     * @param string $categoryName
     * @return void
     */
    public function setCategoryName(?string $categoryName): void
    {
        $this->categoryName = $categoryName;
    }

    /**
     * Get category level
     *
     * @return int
     */
    public function getCategoryLevel(): ?int
    {
        return $this->categoryLevel;
    }

    /**
     * Set category level
     *
     * @param int $categoryLevel
     * @return void
     */
    public function setCategoryLevel(?int $categoryLevel): void
    {
        $this->categoryLevel = $categoryLevel;
    }

    /**
     * Get category url key
     *
     * @return string
     */
    public function getCategoryUrlKey(): ?string
    {
        return $this->categoryUrlKey;
    }

    /**
     * Set category url key
     *
     * @param string $categoryUrlKey
     * @return void
     */
    public function setCategoryUrlKey(?string $categoryUrlKey): void
    {
        $this->categoryUrlKey = $categoryUrlKey;
    }

    /**
     * Get category url path
     *
     * @return string
     */
    public function getCategoryUrlPath(): ?string
    {
        return $this->categoryUrlPath;
    }

    /**
     * Set category url path
     *
     * @param string $categoryUrlPath
     * @return void
     */
    public function setCategoryUrlPath(?string $categoryUrlPath): void
    {
        $this->categoryUrlPath = $categoryUrlPath;
    }
}
