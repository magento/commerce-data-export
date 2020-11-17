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
 * Product entity
 *
 * phpcs:disable Magento2.PHP.FinalImplementation
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Product
{
    /** @var string */
    private $sku;

    /** @var \Magento\CatalogExportApi\Api\Data\ParentProduct[]|null */
    private $parents;

    /** @var string */
    private $storeViewCode;

    /** @var string */
    private $storeCode;

    /** @var string */
    private $websiteCode;

    /** @var string */
    private $name;

    /** @var int */
    private $productId;

    /** @var string */
    private $type;

    /** @var string */
    private $productType;

    /** @var string */
    private $priceView;

    /** @var string */
    private $shortDescription;

    /** @var string */
    private $description;

    /** @var \Magento\CatalogExportApi\Api\Data\ImageDeprecated */
    private $image;

    /** @var bool */
    private $linksExist;

    /** @var bool */
    private $linksPurchasedSeparately;

    /** @var string */
    private $metaDescription;

    /** @var string */
    private $metaKeyword;

    /** @var string */
    private $metaTitle;

    /** @var string */
    private $samplesTitle;

    /** @var \Magento\CatalogExportApi\Api\Data\ImageDeprecated */
    private $smallImage;

    /** @var string */
    private $status;

    /** @var \Magento\CatalogExportApi\Api\Data\ImageDeprecated */
    private $swatchImage;

    /** @var string */
    private $taxClassId;

    /** @var \Magento\CatalogExportApi\Api\Data\ImageDeprecated */
    private $thumbnail;

    /** @var string */
    private $createdAt;

    /** @var string */
    private $updatedAt;

    /** @var string */
    private $modifiedAt;

    /** @var string */
    private $urlKey;

    /** @var string */
    private $visibility;

    /** @var float */
    private $weight;

    /** @var string */
    private $weightUnit;

    /** @var string */
    private $weightType;

    /** @var string */
    private $currency;

    /** @var \Magento\CatalogExportApi\Api\Data\TierPrice[]|null */
    private $tierPrice;

    /** @var string */
    private $deletedAt;

    /** @var bool */
    private $displayable;

    /** @var bool */
    private $buyable;

    /** @var \Magento\CatalogExportApi\Api\Data\Attribute[]|null */
    private $attributes;

    /** @var array */
    private $categories;

    /** @var array */
    private $categoryIds;

    /** @var \Magento\CatalogExportApi\Api\Data\PriceRange */
    private $prices;

    /** @var \Magento\CatalogExportApi\Api\Data\Inventory */
    private $inventory;

    /** @var \Magento\CatalogExportApi\Api\Data\Option[]|null */
    private $options;

    /** @var \Magento\CatalogExportApi\Api\Data\EnteredOption[]|null */
    private $enteredOptions;

    /** @var \Magento\CatalogExportApi\Api\Data\MediaItem[]|null */
    private $mediaGallery;

    /** @var \Magento\CatalogExportApi\Api\Data\ProductOption[]|null */
    private $productOptions;

    /** @var \Magento\CatalogExportApi\Api\Data\ProductShopperInputOption[]|null */
    private $productShopperInputOptions;

    /** @var \Magento\CatalogExportApi\Api\Data\Sample[]|null */
    private $samples;

    /** @var \Magento\CatalogExportApi\Api\Data\Image[]|null */
    private $images;

    /** @var \Magento\CatalogExportApi\Api\Data\Video[]|null */
    private $videos;

    /** @var \Magento\CatalogExportApi\Api\Data\Link[]|null */
    private $links;

    /** @var \Magento\CatalogExportApi\Api\Data\Variant[]|null */
    private $variants;

    /** @var string */
    private $url;

    /** @var bool */
    private $inStock;

    /** @var bool */
    private $lowStock;

    /** @var \Magento\CatalogExportApi\Api\Data\UrlRewrite[]|null */
    private $urlRewrites;

    /**
     * Get sku
     *
     * @return string
     */
    public function getSku(): ?string
    {
        return $this->sku;
    }

    /**
     * Set sku
     *
     * @param string $sku
     * @return void
     */
    public function setSku(?string $sku): void
    {
        $this->sku = $sku;
    }

    /**
     * Get parents
     *
     * @return \Magento\CatalogExportApi\Api\Data\ParentProduct[]|null
     */
    public function getParents(): ?array
    {
        return $this->parents;
    }

    /**
     * Set parents
     *
     * @param \Magento\CatalogExportApi\Api\Data\ParentProduct[] $parents
     * @return void
     */
    public function setParents(?array $parents = null): void
    {
        $this->parents = $parents;
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
     * Get store code
     *
     * @return string
     */
    public function getStoreCode(): ?string
    {
        return $this->storeCode;
    }

    /**
     * Set store code
     *
     * @param string $storeCode
     * @return void
     */
    public function setStoreCode(?string $storeCode): void
    {
        $this->storeCode = $storeCode;
    }

    /**
     * Get website code
     *
     * @return string
     */
    public function getWebsiteCode(): ?string
    {
        return $this->websiteCode;
    }

    /**
     * Set website code
     *
     * @param string $websiteCode
     * @return void
     */
    public function setWebsiteCode(?string $websiteCode): void
    {
        $this->websiteCode = $websiteCode;
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
     * Get product type
     *
     * @return string
     */
    public function getProductType(): ?string
    {
        return $this->productType;
    }

    /**
     * Set product type
     *
     * @param string $productType
     * @return void
     */
    public function setProductType(?string $productType): void
    {
        $this->productType = $productType;
    }

    /**
     * Get price view
     *
     * @return string
     */
    public function getPriceView(): ?string
    {
        return $this->priceView;
    }

    /**
     * Set price view
     *
     * @param string $priceView
     * @return void
     */
    public function setPriceView(?string $priceView): void
    {
        $this->priceView = $priceView;
    }

    /**
     * Get short description
     *
     * @return string
     */
    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    /**
     * Set short description
     *
     * @param string $shortDescription
     * @return void
     */
    public function setShortDescription(?string $shortDescription): void
    {
        $this->shortDescription = $shortDescription;
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
     * Get image
     *
     * @return \Magento\CatalogExportApi\Api\Data\ImageDeprecated
     */
    public function getImage(): ?ImageDeprecated
    {
        return $this->image;
    }

    /**
     * Set image
     *
     * @param \Magento\CatalogExportApi\Api\Data\ImageDeprecated $image
     * @return void
     */
    public function setImage(?ImageDeprecated $image): void
    {
        $this->image = $image;
    }

    /**
     * Get links exist
     *
     * @return bool
     */
    public function getLinksExist(): ?bool
    {
        return $this->linksExist;
    }

    /**
     * Set links exist
     *
     * @param bool $linksExist
     * @return void
     */
    public function setLinksExist(?bool $linksExist): void
    {
        $this->linksExist = $linksExist;
    }

    /**
     * Get links purchased separately
     *
     * @return bool
     */
    public function getLinksPurchasedSeparately(): ?bool
    {
        return $this->linksPurchasedSeparately;
    }

    /**
     * Set links purchased separately
     *
     * @param bool $linksPurchasedSeparately
     * @return void
     */
    public function setLinksPurchasedSeparately(?bool $linksPurchasedSeparately): void
    {
        $this->linksPurchasedSeparately = $linksPurchasedSeparately;
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
     * Get meta keyword
     *
     * @return string
     */
    public function getMetaKeyword(): ?string
    {
        return $this->metaKeyword;
    }

    /**
     * Set meta keyword
     *
     * @param string $metaKeyword
     * @return void
     */
    public function setMetaKeyword(?string $metaKeyword): void
    {
        $this->metaKeyword = $metaKeyword;
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
     * Get samples title
     *
     * @return string
     */
    public function getSamplesTitle(): ?string
    {
        return $this->samplesTitle;
    }

    /**
     * Set samples title
     *
     * @param string $samplesTitle
     * @return void
     */
    public function setSamplesTitle(?string $samplesTitle): void
    {
        $this->samplesTitle = $samplesTitle;
    }

    /**
     * Get small image
     *
     * @return \Magento\CatalogExportApi\Api\Data\ImageDeprecated
     */
    public function getSmallImage(): ?ImageDeprecated
    {
        return $this->smallImage;
    }

    /**
     * Set small image
     *
     * @param \Magento\CatalogExportApi\Api\Data\ImageDeprecated $smallImage
     * @return void
     */
    public function setSmallImage(?ImageDeprecated $smallImage): void
    {
        $this->smallImage = $smallImage;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return void
     */
    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    /**
     * Get swatch image
     *
     * @return \Magento\CatalogExportApi\Api\Data\ImageDeprecated
     */
    public function getSwatchImage(): ?ImageDeprecated
    {
        return $this->swatchImage;
    }

    /**
     * Set swatch image
     *
     * @param \Magento\CatalogExportApi\Api\Data\ImageDeprecated $swatchImage
     * @return void
     */
    public function setSwatchImage(?ImageDeprecated $swatchImage): void
    {
        $this->swatchImage = $swatchImage;
    }

    /**
     * Get tax class id
     *
     * @return string
     */
    public function getTaxClassId(): ?string
    {
        return $this->taxClassId;
    }

    /**
     * Set tax class id
     *
     * @param string $taxClassId
     * @return void
     */
    public function setTaxClassId(?string $taxClassId): void
    {
        $this->taxClassId = $taxClassId;
    }

    /**
     * Get thumbnail
     *
     * @return \Magento\CatalogExportApi\Api\Data\ImageDeprecated
     */
    public function getThumbnail(): ?ImageDeprecated
    {
        return $this->thumbnail;
    }

    /**
     * Set thumbnail
     *
     * @param \Magento\CatalogExportApi\Api\Data\ImageDeprecated $thumbnail
     * @return void
     */
    public function setThumbnail(?ImageDeprecated $thumbnail): void
    {
        $this->thumbnail = $thumbnail;
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
     * Get modified at
     *
     * @return string
     */
    public function getModifiedAt(): ?string
    {
        return $this->modifiedAt;
    }

    /**
     * Set modified at
     *
     * @param string $modifiedAt
     * @return void
     */
    public function setModifiedAt(?string $modifiedAt): void
    {
        $this->modifiedAt = $modifiedAt;
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
     * Get visibility
     *
     * @return string
     */
    public function getVisibility(): ?string
    {
        return $this->visibility;
    }

    /**
     * Set visibility
     *
     * @param string $visibility
     * @return void
     */
    public function setVisibility(?string $visibility): void
    {
        $this->visibility = $visibility;
    }

    /**
     * Get weight
     *
     * @return float
     */
    public function getWeight(): ?float
    {
        return $this->weight;
    }

    /**
     * Set weight
     *
     * @param float $weight
     * @return void
     */
    public function setWeight(?float $weight): void
    {
        $this->weight = $weight;
    }

    /**
     * Get weight unit
     *
     * @return string
     */
    public function getWeightUnit(): ?string
    {
        return $this->weightUnit;
    }

    /**
     * Set weight unit
     *
     * @param string $weightUnit
     * @return void
     */
    public function setWeightUnit(?string $weightUnit): void
    {
        $this->weightUnit = $weightUnit;
    }

    /**
     * Get weight type
     *
     * @return string
     */
    public function getWeightType(): ?string
    {
        return $this->weightType;
    }

    /**
     * Set weight type
     *
     * @param string $weightType
     * @return void
     */
    public function setWeightType(?string $weightType): void
    {
        $this->weightType = $weightType;
    }

    /**
     * Get currency
     *
     * @return string
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * Set currency
     *
     * @param string $currency
     * @return void
     */
    public function setCurrency(?string $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * Get tier price
     *
     * @return \Magento\CatalogExportApi\Api\Data\TierPrice[]|null
     */
    public function getTierPrice(): ?array
    {
        return $this->tierPrice;
    }

    /**
     * Set tier price
     *
     * @param \Magento\CatalogExportApi\Api\Data\TierPrice[] $tierPrice
     * @return void
     */
    public function setTierPrice(?array $tierPrice = null): void
    {
        $this->tierPrice = $tierPrice;
    }

    /**
     * Get deleted at
     *
     * @return string
     */
    public function getDeletedAt(): ?string
    {
        return $this->deletedAt;
    }

    /**
     * Set deleted at
     *
     * @param string $deletedAt
     * @return void
     */
    public function setDeletedAt(?string $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }

    /**
     * Get displayable
     *
     * @return bool
     */
    public function getDisplayable(): ?bool
    {
        return $this->displayable;
    }

    /**
     * Set displayable
     *
     * @param bool $displayable
     * @return void
     */
    public function setDisplayable(?bool $displayable): void
    {
        $this->displayable = $displayable;
    }

    /**
     * Get buyable
     *
     * @return bool
     */
    public function getBuyable(): ?bool
    {
        return $this->buyable;
    }

    /**
     * Set buyable
     *
     * @param bool $buyable
     * @return void
     */
    public function setBuyable(?bool $buyable): void
    {
        $this->buyable = $buyable;
    }

    /**
     * Get attributes
     *
     * @return \Magento\CatalogExportApi\Api\Data\Attribute[]|null
     */
    public function getAttributes(): ?array
    {
        return $this->attributes;
    }

    /**
     * Set attributes
     *
     * @param \Magento\CatalogExportApi\Api\Data\Attribute[] $attributes
     * @return void
     */
    public function setAttributes(?array $attributes = null): void
    {
        $this->attributes = $attributes;
    }

    /**
     * Get categories
     *
     * @return string[]
     */
    public function getCategories(): ?array
    {
        return $this->categories;
    }

    /**
     * Set categories
     *
     * @param string[] $categories
     * @return void
     */
    public function setCategories(?array $categories = null): void
    {
        $this->categories = $categories;
    }

    /**
     * Get category ids
     *
     * @return string[]
     */
    public function getCategoryIds(): ?array
    {
        return $this->categoryIds;
    }

    /**
     * Set category ids
     *
     * @param string[] $categoryIds
     * @return void
     */
    public function setCategoryIds(?array $categoryIds = null): void
    {
        $this->categoryIds = $categoryIds;
    }

    /**
     * Get prices
     *
     * @return \Magento\CatalogExportApi\Api\Data\PriceRange
     */
    public function getPrices(): ?PriceRange
    {
        return $this->prices;
    }

    /**
     * Set prices
     *
     * @param \Magento\CatalogExportApi\Api\Data\PriceRange $prices
     * @return void
     */
    public function setPrices(?PriceRange $prices): void
    {
        $this->prices = $prices;
    }

    /**
     * Get inventory
     *
     * @return \Magento\CatalogExportApi\Api\Data\Inventory
     */
    public function getInventory(): ?Inventory
    {
        return $this->inventory;
    }

    /**
     * Set inventory
     *
     * @param \Magento\CatalogExportApi\Api\Data\Inventory $inventory
     * @return void
     */
    public function setInventory(?Inventory $inventory): void
    {
        $this->inventory = $inventory;
    }

    /**
     * Get options
     *
     * @return \Magento\CatalogExportApi\Api\Data\Option[]|null
     */
    public function getOptions(): ?array
    {
        return $this->options;
    }

    /**
     * Set options
     *
     * @param \Magento\CatalogExportApi\Api\Data\Option[] $options
     * @return void
     */
    public function setOptions(?array $options = null): void
    {
        $this->options = $options;
    }

    /**
     * Get entered options
     *
     * @return \Magento\CatalogExportApi\Api\Data\EnteredOption[]|null
     */
    public function getEnteredOptions(): ?array
    {
        return $this->enteredOptions;
    }

    /**
     * Set entered options
     *
     * @param \Magento\CatalogExportApi\Api\Data\EnteredOption[] $enteredOptions
     * @return void
     */
    public function setEnteredOptions(?array $enteredOptions = null): void
    {
        $this->enteredOptions = $enteredOptions;
    }

    /**
     * Get media gallery
     *
     * @return \Magento\CatalogExportApi\Api\Data\MediaItem[]|null
     */
    public function getMediaGallery(): ?array
    {
        return $this->mediaGallery;
    }

    /**
     * Set media gallery
     *
     * @param \Magento\CatalogExportApi\Api\Data\MediaItem[] $mediaGallery
     * @return void
     */
    public function setMediaGallery(?array $mediaGallery = null): void
    {
        $this->mediaGallery = $mediaGallery;
    }

    /**
     * Get product options
     *
     * @return \Magento\CatalogExportApi\Api\Data\ProductOption[]|null
     */
    public function getProductOptions(): ?array
    {
        return $this->productOptions;
    }

    /**
     * Set product options
     *
     * @param \Magento\CatalogExportApi\Api\Data\ProductOption[] $productOptions
     * @return void
     */
    public function setProductOptions(?array $productOptions = null): void
    {
        $this->productOptions = $productOptions;
    }

    /**
     * Get product shopper input options
     *
     * @return \Magento\CatalogExportApi\Api\Data\ProductShopperInputOption[]|null
     */
    public function getProductShopperInputOptions(): ?array
    {
        return $this->productShopperInputOptions;
    }

    /**
     * Set product shopper input options
     *
     * @param \Magento\CatalogExportApi\Api\Data\ProductShopperInputOption[] $productShopperInputOptions
     * @return void
     */
    public function setProductShopperInputOptions(?array $productShopperInputOptions = null): void
    {
        $this->productShopperInputOptions = $productShopperInputOptions;
    }

    /**
     * Get samples
     *
     * @return \Magento\CatalogExportApi\Api\Data\Sample[]|null
     */
    public function getSamples(): ?array
    {
        return $this->samples;
    }

    /**
     * Set samples
     *
     * @param \Magento\CatalogExportApi\Api\Data\Sample[] $samples
     * @return void
     */
    public function setSamples(?array $samples = null): void
    {
        $this->samples = $samples;
    }

    /**
     * Get images
     *
     * @return \Magento\CatalogExportApi\Api\Data\Image[]|null
     */
    public function getImages(): ?array
    {
        return $this->images;
    }

    /**
     * Set images
     *
     * @param \Magento\CatalogExportApi\Api\Data\Image[] $images
     * @return void
     */
    public function setImages(?array $images = null): void
    {
        $this->images = $images;
    }

    /**
     * Get videos
     *
     * @return \Magento\CatalogExportApi\Api\Data\Video[]|null
     */
    public function getVideos(): ?array
    {
        return $this->videos;
    }

    /**
     * Set videos
     *
     * @param \Magento\CatalogExportApi\Api\Data\Video[] $videos
     * @return void
     */
    public function setVideos(?array $videos = null): void
    {
        $this->videos = $videos;
    }

    /**
     * Get links
     *
     * @return \Magento\CatalogExportApi\Api\Data\Link[]|null
     */
    public function getLinks(): ?array
    {
        return $this->links;
    }

    /**
     * Set links
     *
     * @param \Magento\CatalogExportApi\Api\Data\Link[] $links
     * @return void
     */
    public function setLinks(?array $links = null): void
    {
        $this->links = $links;
    }

    /**
     * Get variants
     *
     * @return \Magento\CatalogExportApi\Api\Data\Variant[]|null
     */
    public function getVariants(): ?array
    {
        return $this->variants;
    }

    /**
     * Set variants
     *
     * @param \Magento\CatalogExportApi\Api\Data\Variant[] $variants
     * @return void
     */
    public function setVariants(?array $variants = null): void
    {
        $this->variants = $variants;
    }

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
     * Get in stock
     *
     * @return bool
     */
    public function getInStock(): ?bool
    {
        return $this->inStock;
    }

    /**
     * Set in stock
     *
     * @param bool $inStock
     * @return void
     */
    public function setInStock(?bool $inStock): void
    {
        $this->inStock = $inStock;
    }

    /**
     * Get low stock
     *
     * @return bool
     */
    public function getLowStock(): ?bool
    {
        return $this->lowStock;
    }

    /**
     * Set low stock
     *
     * @param bool $lowStock
     * @return void
     */
    public function setLowStock(?bool $lowStock): void
    {
        $this->lowStock = $lowStock;
    }

    /**
     * Get url rewrites
     *
     * @return \Magento\CatalogExportApi\Api\Data\UrlRewrite[]|null
     */
    public function getUrlRewrites(): ?array
    {
        return $this->urlRewrites;
    }

    /**
     * Set url rewrites
     *
     * @param \Magento\CatalogExportApi\Api\Data\UrlRewrite[] $urlRewrites
     * @return void
     */
    public function setUrlRewrites(?array $urlRewrites = null): void
    {
        $this->urlRewrites = $urlRewrites;
    }
}
