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
 * Category entity
 *
 * phpcs:disable Magento2.PHP.FinalImplementation
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Category
{
    /** @var string */
    private $categoryId;

    /** @var string */
    private $storeViewCode;

    /** @var int */
    private $isActive;

    /** @var int */
    private $isAnchor;

    /** @var string */
    private $displayMode;

    /** @var string */
    private $description;

    /** @var string */
    private $metaTitle;

    /** @var string */
    private $metaKeywords;

    /** @var string */
    private $metaDescription;

    /** @var string */
    private $name;

    /** @var int */
    private $childrenCount;

    /** @var int */
    private $includeInMenu;

    /** @var string */
    private $path;

    /** @var string */
    private $pathInStore;

    /** @var string */
    private $urlKey;

    /** @var string */
    private $urlPath;

    /** @var string */
    private $image;

    /** @var int */
    private $position;

    /** @var int */
    private $level;

    /** @var int */
    private $parentId;

    /** @var string */
    private $createdAt;

    /** @var string */
    private $updatedAt;

    /** @var int */
    private $productCount;

    /** @var array */
    private $availableSortBy;

    /** @var string */
    private $defaultSortBy;

    /** @var \Magento\CatalogExportApi\Api\Data\Breadcrumbs[]|null */
    private $breadcrumbs;

    /** @var array */
    private $children;

    /** @var string */
    private $canonicalUrl;

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
     * Get store view code
     *
     * @return string
     */
    public function getStoreViewCode(): ?string
    {
        return $this->storeViewCode;
    }

    /**
     * Set store view code
     *
     * @param string $storeViewCode
     * @return void
     */
    public function setStoreViewCode(?string $storeViewCode): void
    {
        $this->storeViewCode = $storeViewCode;
    }

    /**
     * Get is active
     *
     * @return int
     */
    public function getIsActive(): ?int
    {
        return $this->isActive;
    }

    /**
     * Set is active
     *
     * @param int $isActive
     * @return void
     */
    public function setIsActive(?int $isActive): void
    {
        $this->isActive = $isActive;
    }

    /**
     * Get is anchor
     *
     * @return int
     */
    public function getIsAnchor(): ?int
    {
        return $this->isAnchor;
    }

    /**
     * Set is anchor
     *
     * @param int $isAnchor
     * @return void
     */
    public function setIsAnchor(?int $isAnchor): void
    {
        $this->isAnchor = $isAnchor;
    }

    /**
     * Get display mode
     *
     * @return string
     */
    public function getDisplayMode(): ?string
    {
        return $this->displayMode;
    }

    /**
     * Set display mode
     *
     * @param string $displayMode
     * @return void
     */
    public function setDisplayMode(?string $displayMode): void
    {
        $this->displayMode = $displayMode;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return void
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * Get meta title
     *
     * @return string
     */
    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    /**
     * Set meta title
     *
     * @param string $metaTitle
     * @return void
     */
    public function setMetaTitle(?string $metaTitle): void
    {
        $this->metaTitle = $metaTitle;
    }

    /**
     * Get meta keywords
     *
     * @return string
     */
    public function getMetaKeywords(): ?string
    {
        return $this->metaKeywords;
    }

    /**
     * Set meta keywords
     *
     * @param string $metaKeywords
     * @return void
     */
    public function setMetaKeywords(?string $metaKeywords): void
    {
        $this->metaKeywords = $metaKeywords;
    }

    /**
     * Get meta description
     *
     * @return string
     */
    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    /**
     * Set meta description
     *
     * @param string $metaDescription
     * @return void
     */
    public function setMetaDescription(?string $metaDescription): void
    {
        $this->metaDescription = $metaDescription;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return void
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get children count
     *
     * @return int
     */
    public function getChildrenCount(): ?int
    {
        return $this->childrenCount;
    }

    /**
     * Set children count
     *
     * @param int $childrenCount
     * @return void
     */
    public function setChildrenCount(?int $childrenCount): void
    {
        $this->childrenCount = $childrenCount;
    }

    /**
     * Get include in menu
     *
     * @return int
     */
    public function getIncludeInMenu(): ?int
    {
        return $this->includeInMenu;
    }

    /**
     * Set include in menu
     *
     * @param int $includeInMenu
     * @return void
     */
    public function setIncludeInMenu(?int $includeInMenu): void
    {
        $this->includeInMenu = $includeInMenu;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * Set path
     *
     * @param string $path
     * @return void
     */
    public function setPath(?string $path): void
    {
        $this->path = $path;
    }

    /**
     * Get path in store
     *
     * @return string
     */
    public function getPathInStore(): ?string
    {
        return $this->pathInStore;
    }

    /**
     * Set path in store
     *
     * @param string $pathInStore
     * @return void
     */
    public function setPathInStore(?string $pathInStore): void
    {
        $this->pathInStore = $pathInStore;
    }

    /**
     * Get url key
     *
     * @return string
     */
    public function getUrlKey(): ?string
    {
        return $this->urlKey;
    }

    /**
     * Set url key
     *
     * @param string $urlKey
     * @return void
     */
    public function setUrlKey(?string $urlKey): void
    {
        $this->urlKey = $urlKey;
    }

    /**
     * Get url path
     *
     * @return string
     */
    public function getUrlPath(): ?string
    {
        return $this->urlPath;
    }

    /**
     * Set url path
     *
     * @param string $urlPath
     * @return void
     */
    public function setUrlPath(?string $urlPath): void
    {
        $this->urlPath = $urlPath;
    }

    /**
     * Get image
     *
     * @return string
     */
    public function getImage(): ?string
    {
        return $this->image;
    }

    /**
     * Set image
     *
     * @param string $image
     * @return void
     */
    public function setImage(?string $image): void
    {
        $this->image = $image;
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

    /**
     * Get level
     *
     * @return int
     */
    public function getLevel(): ?int
    {
        return $this->level;
    }

    /**
     * Set level
     *
     * @param int $level
     * @return void
     */
    public function setLevel(?int $level): void
    {
        $this->level = $level;
    }

    /**
     * Get parent id
     *
     * @return int
     */
    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    /**
     * Set parent id
     *
     * @param int $parentId
     * @return void
     */
    public function setParentId(?int $parentId): void
    {
        $this->parentId = $parentId;
    }

    /**
     * Get created at
     *
     * @return string
     */
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    /**
     * Set created at
     *
     * @param string $createdAt
     * @return void
     */
    public function setCreatedAt(?string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Get updated at
     *
     * @return string
     */
    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    /**
     * Set updated at
     *
     * @param string $updatedAt
     * @return void
     */
    public function setUpdatedAt(?string $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Get product count
     *
     * @return int
     */
    public function getProductCount(): ?int
    {
        return $this->productCount;
    }

    /**
     * Set product count
     *
     * @param int $productCount
     * @return void
     */
    public function setProductCount(?int $productCount): void
    {
        $this->productCount = $productCount;
    }

    /**
     * Get available sort by
     *
     * @return string[]
     */
    public function getAvailableSortBy(): ?array
    {
        return $this->availableSortBy;
    }

    /**
     * Set available sort by
     *
     * @param string[] $availableSortBy
     * @return void
     */
    public function setAvailableSortBy(?array $availableSortBy = null): void
    {
        $this->availableSortBy = $availableSortBy;
    }

    /**
     * Get default sort by
     *
     * @return string
     */
    public function getDefaultSortBy(): ?string
    {
        return $this->defaultSortBy;
    }

    /**
     * Set default sort by
     *
     * @param string $defaultSortBy
     * @return void
     */
    public function setDefaultSortBy(?string $defaultSortBy): void
    {
        $this->defaultSortBy = $defaultSortBy;
    }

    /**
     * Get breadcrumbs
     *
     * @return \Magento\CatalogExportApi\Api\Data\Breadcrumbs[]|null
     */
    public function getBreadcrumbs(): ?array
    {
        return $this->breadcrumbs;
    }

    /**
     * Set breadcrumbs
     *
     * @param \Magento\CatalogExportApi\Api\Data\Breadcrumbs[] $breadcrumbs
     * @return void
     */
    public function setBreadcrumbs(?array $breadcrumbs = null): void
    {
        $this->breadcrumbs = $breadcrumbs;
    }

    /**
     * Get children
     *
     * @return string[]
     */
    public function getChildren(): ?array
    {
        return $this->children;
    }

    /**
     * Set children
     *
     * @param string[] $children
     * @return void
     */
    public function setChildren(?array $children = null): void
    {
        $this->children = $children;
    }

    /**
     * Get canonical url
     *
     * @return string
     */
    public function getCanonicalUrl(): ?string
    {
        return $this->canonicalUrl;
    }

    /**
     * Set canonical url
     *
     * @param string $canonicalUrl
     * @return void
     */
    public function setCanonicalUrl(?string $canonicalUrl): void
    {
        $this->canonicalUrl = $canonicalUrl;
    }
}
