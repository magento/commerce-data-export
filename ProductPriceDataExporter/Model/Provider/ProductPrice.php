<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\ProductPriceDataExporter\Model\Provider;

use Magento\Bundle\Model\Product\Price as BundlePrice;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\DataExporter\Export\DataProcessorInterface;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Downloadable\Model\Product\Type as Downloadable;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\ProductPriceDataExporter\Model\Query\CustomerGroupPricesQuery;
use Magento\ProductPriceDataExporter\Model\Query\ParentProductsQuery;
use Magento\ProductPriceDataExporter\Model\Query\ProductPricesQuery;
use Magento\Framework\DB\Select;

/**
 * Collect raw product prices: regular price and discounts: special price, customer group price, rule price, ...
 *
 * Fallback price - price used as fallback if price for given scope (<website>, <customer group>) not found
 * Fallback price scope - <product website, ProductPrice::FALLBACK_CUSTOMER_GROUP>
 * If Customer Group "All Groups" selected with qty=1, group price will be added to fallback price
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductPrice implements DataProcessorInterface
{
    private const FALLBACK_CUSTOMER_GROUP = "0";
    private const GLOBAL_STORE_ID = 0;
    private const REGULAR_PRICE = 'regular';
    private const BUNDLE_FIXED = 'BUNDLE_FIXED';
    private const BUNDLE_DYNAMIC = 'BUNDLE_DYNAMIC';

    /**
     * mapping for ProductPriceAggregate.type
     */
    private const PRODUCT_TYPE = [
        self::BUNDLE_FIXED,
        self::BUNDLE_DYNAMIC,
        Type::TYPE_SIMPLE,
        Configurable::TYPE_CODE,
        Downloadable::TYPE_DOWNLOADABLE,
        Grouped::TYPE_CODE,
        Type::TYPE_BUNDLE,
        'giftcard', // represent giftcard product type
    ];

    private ResourceConnection $resourceConnection;
    private ProductPricesQuery $pricesQuery;
    private CustomerGroupPricesQuery $customerGroupPricesQuery;
    private DateTime $dateTime;
    private CommerceDataExportLoggerInterface $logger;
    private Config $eavConfig;

    private ?array $priceAttributes = null;

    private ?array $websitesByStore = null;

    private ?array $websites = null;

    /**
     * @var ParentProductsQuery
     */
    private ParentProductsQuery $parentProductsQuery;

    /**
     * @param ProductPricesQuery $pricesQuery
     * @param CustomerGroupPricesQuery $customerGroupPricesQuery
     * @param ResourceConnection $resourceConnection
     * @param DateTime $dateTime
     * @param Config $eavConfig
     * @param CommerceDataExportLoggerInterface $logger
     * @param ParentProductsQuery $parentProductsQuery
     */
    public function __construct(
        ProductPricesQuery $pricesQuery,
        CustomerGroupPricesQuery $customerGroupPricesQuery,
        ResourceConnection $resourceConnection,
        DateTime $dateTime,
        Config $eavConfig,
        CommerceDataExportLoggerInterface $logger,
        ParentProductsQuery $parentProductsQuery
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->pricesQuery = $pricesQuery;
        $this->customerGroupPricesQuery = $customerGroupPricesQuery;
        $this->dateTime = $dateTime;
        $this->logger = $logger;
        $this->eavConfig = $eavConfig;
        $this->parentProductsQuery = $parentProductsQuery;
    }

    /**
     * Execute product prices collecting
     *
     * @param array $arguments
     * @param callable $dataProcessorCallback
     * @param FeedIndexMetadata $metadata
     * @param ? $node
     * @param ? $info
     * @return void
     * @throws LocalizedException
     * @throws UnableRetrieveData
     * @throws \Zend_Db_Statement_Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function execute(
        array $arguments,
        callable $dataProcessorCallback,
        FeedIndexMetadata $metadata,
        $node = null,
        $info = null
    ): void {
        $ids = array_column($arguments, 'productId');
        $productPriceTemplate = $this->getProductPriceTemplate($ids);

        $select = $this->pricesQuery->getQuery($ids, $this->getPriceAttributes());
        // update price template: set price if global price set, set bundle price type
        $pricesRawData = $this->updatePriceTemplate($select, $productPriceTemplate);

        // build up fallback prices
        $fallbackPrices = $this->buildFallbackPrices($pricesRawData, $productPriceTemplate);

        // add prices to fallback prices that were set only on global level
        foreach ($productPriceTemplate as $productId => $websites) {
            foreach ($websites as $websiteId => $productTemplate) {
                $key = $this->buildKey($productId, $websiteId, self::FALLBACK_CUSTOMER_GROUP);
                $fallbackPrices[$key] = $productTemplate;
            }
        }

        $filteredIds = array_unique(array_column($fallbackPrices, 'productId'));
        // Add customer group prices to fallback records before processing
        $this->addFallbackCustomerGroupPrices($fallbackPrices, $filteredIds);

        // process fallback prices
        $this->processFallbackPrices($fallbackPrices, $dataProcessorCallback, $metadata);

        $this->addCustomerGroupPrices($fallbackPrices, $filteredIds, $dataProcessorCallback, $metadata);
    }

    /**
     * Update price template
     *
     * @param Select $select
     * @param array $productPriceTemplate
     * @return array
     * @throws LocalizedException
     * @throws UnableRetrieveData
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function updatePriceTemplate(Select $select, array &$productPriceTemplate): array
    {
        $cursor = $this->resourceConnection->getConnection()->query($select);

        $pricesRawData = [];
        while ($row = $cursor->fetch()) {
            $percentageDiscount = null;
            $productId = $row['entity_id'];

            if (!$this->productTemplateExists($productPriceTemplate, $productId)) {
                continue;
            }

            if ($row['type_id'] === Type::TYPE_BUNDLE) {
                $row['type_id'] = (int)$row['price_type'] === BundlePrice::PRICE_TYPE_FIXED
                    ? self::BUNDLE_FIXED
                    : self::BUNDLE_DYNAMIC;
                $specialPrice = $row[ProductAttributeInterface::CODE_SPECIAL_PRICE] ?? null;
                if ($specialPrice) {
                    $percentageDiscount = $specialPrice;
                }
                foreach ($productPriceTemplate[$productId] as &$productTemplate) {
                    $productTemplate['type'] = $row['type_id'];
                }
                unset($productTemplate);
            }

            $rawPriceAdded = false;
            foreach ($this->getPriceAttributes() as $attributeCode) {
                $price = $row[$attributeCode];
                $priceStoreId = $row[$attributeCode . '_storeId'];
                if ($priceStoreId === null) {
                    continue;
                }
                if ($this->isGlobalPrice($priceStoreId)) {
                    foreach ($productPriceTemplate[$productId] as &$productTemplate) {
                        if ($attributeCode === self::REGULAR_PRICE && $price !== null) {
                            $productTemplate[self::REGULAR_PRICE] = (float)$price;
                        } elseif ($price !== null) {
                            $this->addDiscountPrice($productTemplate, $attributeCode, $price, $percentageDiscount);
                        }
                    }
                    unset($productTemplate);
                } elseif (!$rawPriceAdded) {
                    $pricesRawData[] = $row;
                    $rawPriceAdded = true;
                }
            }
        }
        return $pricesRawData;
    }

    /**
     * Build fallback prices for each product and website combination
     *
     * @param array $pricesRawData
     * @param array $productPriceTemplate
     * @return array
     * @throws LocalizedException
     * @throws UnableRetrieveData
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function buildFallbackPrices(array $pricesRawData, array &$productPriceTemplate): array
    {
        $fallbackPrices = [];
        // build up fallback prices
        foreach ($pricesRawData as $row) {
            $percentageDiscount = null;
            $productId = $row['entity_id'];

            foreach ($this->getPriceAttributes() as $attributeCode) {
                $price = $row[$attributeCode];
                $priceStoreId = $row[$attributeCode . '_storeId'];
                if ($this->isGlobalPrice($priceStoreId)) {
                    continue;
                }
                if ($priceStoreId === null) {
                    continue;
                }
                $websiteId = $this->getWebsiteIdFromStoreId($priceStoreId);
                $key = $this->buildKey($row['entity_id'], $websiteId, self::FALLBACK_CUSTOMER_GROUP);

                if (!isset($fallbackPrices[$key])) {
                    if (!$this->productTemplateExists($productPriceTemplate, $productId, $websiteId)) {
                        // eav table may contain price for product not assigned to website
                        continue;
                    }
                    $fallbackPrices[$key] = $productPriceTemplate[$productId][$websiteId];
                    unset($productPriceTemplate[$productId][$websiteId]);
                }

                if ($attributeCode === self::REGULAR_PRICE && $price !== null) {
                    if ($this->shouldOverrideGlobalPrice($fallbackPrices[$key], self::REGULAR_PRICE, $row)) {
                        $fallbackPrices[$key][self::REGULAR_PRICE] = (float)$price;
                    }
                } elseif ($this->shouldOverrideGlobalPrice($fallbackPrices[$key], $attributeCode, $row)) {
                    if ($price === null) {
                        // cover case when special price was set on global level but unset on store level
                        $fallbackPrices[$key]['discounts'] = [];
                    } else {
                        if ($attributeCode === 'special_price'
                            && \in_array(
                                $fallbackPrices[$key]['type'],
                                [self::BUNDLE_DYNAMIC, self::BUNDLE_FIXED],
                                true
                            )) {
                            $percentageDiscount = $price;
                        }
                        $this->addDiscountPrice($fallbackPrices[$key], $attributeCode, $price, $percentageDiscount);
                    }
                }
            }
        }
        return $fallbackPrices;
    }

    /**
     * Process fallback prices in batches
     *
     * @param array $fallbackPrices
     * @param callable $dataProcessorCallback
     * @param FeedIndexMetadata $metadata
     * @return void
     */
    private function processFallbackPrices(
        array $fallbackPrices,
        callable $dataProcessorCallback,
        FeedIndexMetadata $metadata
    ): void {
        $itemN = 0;
        $prices = [];
        foreach ($fallbackPrices as $fallbackPrice) {
            $itemN++;
            $prices[] = $fallbackPrice;
            if ($itemN % $metadata->getBatchSize() === 0) {
                $dataProcessorCallback($this->get($prices));
                $prices = [];
            }
        }
        if ($prices) {
            $dataProcessorCallback($this->get($prices));
        }
    }

    /**
     * Check if product template exists. Product can be unassigned from website but price may still exist in eav table
     *
     * @param array $productPriceTemplate
     * @param string $productId
     * @param int|null $websiteId
     * @return bool
     */
    private function productTemplateExists(array $productPriceTemplate, $productId, $websiteId = null): bool
    {
        return $websiteId !== null
            ? isset($productPriceTemplate[$productId][$websiteId])
            : isset($productPriceTemplate[$productId]);
    }

    private function getWebsiteIdFromStoreId($storeId): string
    {
        $this->loadWebsites();
        return isset($this->websitesByStore[$storeId]) ? $this->websitesByStore[$storeId]['website_id'] : "0";
    }

    private function getWebsites(): array
    {
        if ($this->websites === null) {
            $this->loadWebsites();
            $this->websites = array_unique(\array_filter(
                array_column($this->websitesByStore, 'website_id'),
                function ($websiteId) {
                    return (int)$websiteId !== self::GLOBAL_STORE_ID;
                }
            ));
        }
        return $this->websites;
    }

    private function getWebsiteIds(array $row, array $websites): array
    {
        if ($this->isGlobalWebsite($row)) {
            // cover case for tier prices "all websites"
            return $websites;
        } else {
            return [$row['website_id']];
        }
    }

    private function loadWebsites(): void
    {
        if ($this->websitesByStore === null) {
            $this->websitesByStore = $this->resourceConnection->getConnection()->fetchAssoc(
                $this->pricesQuery->getWebsitesQuery()
            );
        }
    }

    private function isGlobalPrice($storeViewId): bool
    {
        return (int)$storeViewId === self::GLOBAL_STORE_ID;
    }

    private function isGlobalWebsite(array $row): bool
    {
        return (int)$row['website_id'] === self::GLOBAL_STORE_ID;
    }

    /**
     * This method is used to generate a template for product prices for each product and website combination
     *
     * @param array $productIds An array of product IDs for which the price templates are to be generated.
     * @return array An array of price templates, indexed by product ID and website ID.
     */
    private function getProductPriceTemplate(
        array $productIds
    ): array {
        $cursor = $this->resourceConnection->getConnection()->query(
            $this->pricesQuery->getProductWebsiteAssociations($productIds)
        );
        $pricesTemplate = [];
        $parentProducts = $this->getParentProductSkus($productIds);
        while ($row = $cursor->fetch()) {
            $key = $this->buildKey($row['entity_id'], $row['website_id'], self::FALLBACK_CUSTOMER_GROUP);
            if (!isset($pricesTemplate[$key])) {
                $template = [
                    // system fields required for handle product / website deletion. Not used in feed payload
                    'productId' => $row['entity_id'],
                    'website_id' => $row['website_id'],
                    'productPriceId' => $key,

                    // feed fields
                    'sku' => $row['sku'],
                    'websiteCode' => $row['websiteCode'],
                    'type' => $this->convertProductType($row['type_id']),
                    self::REGULAR_PRICE => 0.,
                    'updatedAt' => $this->dateTime->formatDate(time()),
                    'customerGroupCode' => self::FALLBACK_CUSTOMER_GROUP,
                    'parents' => $parentProducts[$key] ?? null,
                    'discounts' => [],
                    'deleted' => false,
                ];
                $pricesTemplate[$row['entity_id']][$row['website_id']] = $template;
            }
        }

        return $pricesTemplate;
    }

    /**
     * SQL query may return multiple rows for the same product but with different price per price attribute.
     *
     * Website-specific price must override global price
     *
     * @param array $fallbackPrice
     * @param string $attribute
     * @param array $priceRow
     * @return bool
     */
    private function shouldOverrideGlobalPrice(array $fallbackPrice, string $attribute, array $priceRow): bool
    {
        $priceStoreId = (int)$priceRow[$attribute . '_storeId'];
        if ($attribute === self::REGULAR_PRICE) {
            return !isset($fallbackPrice[$attribute]) || $priceStoreId !== self::GLOBAL_STORE_ID;
        } else {
            $discounts = $fallbackPrice['discounts'] ?? null;
            if (!$discounts) {
                return true;
            }
            foreach ($discounts as $discount) {
                if ($discount['code'] === $attribute) {
                    return $priceStoreId !== self::GLOBAL_STORE_ID;
                }
            }
        }
        return true;
    }

    /**
     * Get price attributes
     *
     * @return array
     * @throws UnableRetrieveData|LocalizedException
     */
    private function getPriceAttributes(): array
    {
        if ($this->priceAttributes === null) {
            $attribute = $this->eavConfig->getAttribute(Product::ENTITY, ProductAttributeInterface::CODE_PRICE);
            if ($attribute) {
                $this->priceAttributes[$attribute->getId()] = self::REGULAR_PRICE;
            }
            $attribute = $this->eavConfig->getAttribute(Product::ENTITY, ProductAttributeInterface::CODE_SPECIAL_PRICE);
            if ($attribute) {
                $this->priceAttributes[$attribute->getId()] = ProductAttributeInterface::CODE_SPECIAL_PRICE;
            }
        }
        if (!$this->priceAttributes) {
            throw new UnableRetrieveData('Price attributes not found');
        }
        return $this->priceAttributes;
    }

    /**
     * For backward compatibility - to allow 3rd party plugins work
     *
     * @param array $values
     * @return array
     */
    public function get(array $values) : array
    {
        return $values;
    }

    /**
     * Add Customer Group Prices
     *
     * Handle customer group and catalog rule prices
     *
     * @param array $fallbackPrices
     * @param array $productIds
     * @param callable $dataProcessorCallback
     * @param FeedIndexMetadata $metadata
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    private function addCustomerGroupPrices(
        array $fallbackPrices,
        array $productIds,
        callable $dataProcessorCallback,
        FeedIndexMetadata $metadata
    ): void {
        $cursor = $this->resourceConnection->getConnection()
            ->query($this->customerGroupPricesQuery->getQuery($productIds));
        $itemN = 0;
        $prices = [];
        $allWebsites = $this->getWebsites();
        while ($row = $cursor->fetch()) {
            foreach ($this->getWebsiteIds($row, $allWebsites) as $websiteId) {
                $itemN++;
                $customerGroupId = $row['customer_group_id'];
                $key = $this->buildKey($row['entity_id'], $websiteId, $customerGroupId . $row['all_groups']);
                $keyFallback = $this->buildKey($row['entity_id'], $websiteId, self::FALLBACK_CUSTOMER_GROUP);
                $fallbackPrice = $fallbackPrices[$keyFallback] ?? null;
                if (!$fallbackPrice) {
                    // since we iterate over all websites fallback price may not exist (unassigned from website)
                    continue;
                }
                // To handle tier prices
                if ($row['all_groups'] !== null) {
                    $priceValue = $row['group_price'] ?? null;
                    $pricePercentage = $row['percentage_value'] ?? null;

                    // copy feed data from fallbackPrice for each row of customer group price
                    $prices[$key] = $fallbackPrice;

                    // override customer group specific fields
                    $this->addDiscountPrice($prices[$key], 'group', $priceValue, $pricePercentage);
                }

                // To handle only catalog rule prices
                if ($row['rule_price'] !== null) {
                    if (!isset($prices[$key])) {
                        $prices[$key] = $fallbackPrice;
                    }
                    $this->addDiscountPrice($prices[$key], 'catalog_rule', $row['rule_price']);
                }

                $prices[$key]['customerGroupCode'] = $this->buildCustomerGroupCode($customerGroupId);
                $prices[$key]['productPriceId'] = $key;

                if ($itemN % $metadata->getBatchSize() === 0) {
                    $dataProcessorCallback($this->get($prices));
                    $prices = [];
                }
            }
        }
        if ($prices) {
            $dataProcessorCallback($this->get($prices));
        }
    }

    /**
     * Add Customer Group Prices to fallback price
     *
     * @param array $fallbackPrices
     * @param array $productIds
     * @return void
     * @throws \Zend_Db_Statement_Exception
     * @throws \Exception
     */
    private function addFallbackCustomerGroupPrices(
        array &$fallbackPrices,
        array $productIds
    ): void {
        $cursor = $this->resourceConnection->getConnection()
            ->query($this->customerGroupPricesQuery->getCustomerGroupFallbackQuery($productIds));
        $allWebsites = $this->getWebsites();
        while ($row = $cursor->fetch()) {
            foreach ($this->getWebsiteIds($row, $allWebsites) as $websiteId) {
                $keyFallback = $this->buildKey($row['entity_id'], $websiteId, self::FALLBACK_CUSTOMER_GROUP);
                // since we iterate over all websites fallback price may not exist - skipping
                if (!isset($fallbackPrices[$keyFallback])) {
                    continue;
                }
                $priceValue = $row['group_price'] ?? null;
                $pricePercentage = $row['percentage_value'] ?? null;
                $this->addDiscountPrice($fallbackPrices[$keyFallback], 'group', $priceValue, $pricePercentage);
            }
        }
    }

    /**
     * Build Key
     *
     * @param int|string $productId
     * @param int|string $websiteId
     * @param int|string $customerGroup
     * @return string
     */
    private function buildKey(int|string $productId, int|string $websiteId, int|string $customerGroup): string
    {
        return implode('-', [$productId, $websiteId, $customerGroup]);
    }

    /**
     * Add Discount Price
     *
     * @param array $priceFeedItem
     * @param string $code
     * @param ?string $price
     * @param ?string $percentage
     * @return void
     */
    private function addDiscountPrice(
        array &$priceFeedItem,
        string $code,
        string $price = null,
        string $percentage = null
    ): void {
        foreach ($priceFeedItem['discounts'] as &$discount) {
            if ($discount['code'] === $code) {
                $this->setPriceOrPercentageDiscount($discount, $price, $percentage);
                return;
            }
        }
        unset($discount);

        if (null === $price && null === $percentage) {
            return;
        }

        $priceDiscount['code'] = $code;
        $this->setPriceOrPercentageDiscount($priceDiscount, $price, $percentage);
        $priceFeedItem['discounts'][] = $priceDiscount;
    }

    /**
     * Get Parent Product Skus by product ids
     *
     * @param array $productIds
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    private function getParentProductSkus(array $productIds): array
    {
        $cursor = $this->resourceConnection->getConnection()->query(
            $this->parentProductsQuery->getQuery($productIds)
        );
        $parentProducts = [];
        while ($row = $cursor->fetch()) {
            $key = $this->buildKey($row['child_id'], $row['website_id'], self::FALLBACK_CUSTOMER_GROUP);
            $parentProducts[$key][] = [
                'type' => $this->convertProductType($row['type_id']),
                'sku' => $row['sku'],
            ];
        }
        return $parentProducts;
    }

    /**
     * Convert Product Type
     *
     * @param string $typeId
     * @return string
     */
    private function convertProductType(string $typeId): string
    {
        $productType = \in_array($typeId, self::PRODUCT_TYPE, true) ? $typeId : Type::TYPE_SIMPLE;

        return strtoupper($productType);
    }

    /**
     * Build customer group code from the customer group id. Using sha1 to generate the code
     *
     * @param string $customerGroupId
     * @return string
     */
    private function buildCustomerGroupCode(string $customerGroupId): string
    {
        return sha1($customerGroupId);
    }

    /**
     * Check if fixed price or percentage discount should be applied
     *
     * @param array $discount
     * @param ?string $price
     * @param ?string $percent
     * @return void
     */
    private function setPriceOrPercentageDiscount(array &$discount, string $price = null, string $percent = null): void
    {
        if (null !== $percent) {
            $discount['percentage'] = (float)$percent;
            unset($discount['price']);
        } elseif (null !== $price) {
            $discount['price'] = (float)$price;
            unset($discount['percentage']);
        }
    }
}
