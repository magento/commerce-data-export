<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductPriceDataExporter\Model\Provider;

use Magento\Bundle\Model\Product\Price as BundlePrice;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Downloadable\Model\Product\Type as Downloadable;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\ProductPriceDataExporter\Model\Query\CustomerGroupPricesQuery;
use Magento\ProductPriceDataExporter\Model\Query\CatalogRulePricesQuery;
use Magento\ProductPriceDataExporter\Model\Query\ProductPricesQuery;

/**
 * Collect raw product prices: regular price and discounts: special price, customer group price, rule price, ...
 *
 * Fallback price - price used as fallback if price for given scope (<website>, <customer group>) not found
 * Fallback price scope - <product website, ProductPrice::FALLBACK_CUSTOMER_GROUP>
 * If Customer Group "All Groups" selected with qty=1, group price will be added to fallback price
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductPrice
{
    private const FALLBACK_CUSTOMER_GROUP = "0";
    private const PRICE_SCOPE_KEY_PART = 0;
    private const REGULAR_PRICE = 'price';
    private const UNKNOWN_PRICE_CODE = 'unknown';
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
    private CatalogRulePricesQuery $catalogRulePricesQuery;
    private DeleteFeedItems $deleteFeedItems;
    private CommerceDataExportLoggerInterface $logger;
    private Config $eavConfig;

    /**
     * @var array|null
     */
    private ?array $priceAttributes = null;

    /**
     * @param ProductPricesQuery $pricesQuery
     * @param CustomerGroupPricesQuery $customerGroupPricesQuery
     * @param CatalogRulePricesQuery $catalogRulePricesQuery
     * @param ResourceConnection $resourceConnection
     * @param DeleteFeedItems $deleteFeedItems
     * @param DateTime $dateTime
     * @param Config $eavConfig
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        ProductPricesQuery $pricesQuery,
        CustomerGroupPricesQuery $customerGroupPricesQuery,
        CatalogRulePricesQuery $catalogRulePricesQuery,
        ResourceConnection $resourceConnection,
        DeleteFeedItems $deleteFeedItems,
        DateTime $dateTime,
        Config $eavConfig,
        CommerceDataExportLoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->pricesQuery = $pricesQuery;
        $this->customerGroupPricesQuery = $customerGroupPricesQuery;
        $this->dateTime = $dateTime;
        $this->catalogRulePricesQuery = $catalogRulePricesQuery;
        $this->deleteFeedItems = $deleteFeedItems;
        $this->logger = $logger;
        $this->eavConfig = $eavConfig;
    }

    /**
     * Get ProductPrice
     *
     * @param array $values
     * @return array
     * @throws UnableRetrieveData|LocalizedException
     * @throws \Zend_Db_Statement_Exception
     * @throws \Exception
     */
    public function get(array $values): array
    {
        $ids = array_column($values, 'productId');
        $cursor = $this->resourceConnection->getConnection()->query(
            $this->pricesQuery->getQuery($ids, \array_keys($this->getPriceAttributes()))
        );
        $output = [];
        while ($row = $cursor->fetch()) {
            $percentageDiscount = null;
            $priceAttributeCode = $this->resolvePriceCode($row);
            if ($row['type_id'] === Type::TYPE_BUNDLE) {
                $row['type_id'] = (int)$row['price_type'] === BundlePrice::PRICE_TYPE_FIXED
                    ? self::BUNDLE_FIXED
                    : self::BUNDLE_DYNAMIC;
                if ($priceAttributeCode === ProductAttributeInterface::CODE_SPECIAL_PRICE) {
                    $percentageDiscount = $row['price'];
                }
            }
            $key = $this->buildKey($row['entity_id'], $row['website_id'], self::FALLBACK_CUSTOMER_GROUP);
            if (!isset($output[$key])) {
                $output[$key] = $this->fillOutput($row, $key);
            }
            if ($priceAttributeCode === self::REGULAR_PRICE) {
                $output[$key]['regular'] = (float)$row['price'];
            } elseif ($priceAttributeCode !== self::UNKNOWN_PRICE_CODE) {
                $this->addDiscountPrice($output[$key], $priceAttributeCode, $row['price'], $percentageDiscount);
            }

            // cover case when _this_ product type doesn't have regular price, but this field is required in schema
            if (!isset($output[$key]['regular'])) {
                $output[$key]['regular'] = 0.;
            }
        }
        $filteredIds = array_unique(array_column($output, 'productId'));
        $this->addCustomerGroupPrices($output, $filteredIds);
        $this->addCatalogRulePrices($output, $filteredIds);

        $this->setItemsToDelete($output);

        return $output;
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
            $attribute = $this->eavConfig->getAttribute(Product::ENTITY, 'price');
            if ($attribute) {
                $this->priceAttributes[$attribute->getId()] = 'price';
            }
            $attribute = $this->eavConfig->getAttribute(Product::ENTITY, 'special_price');
            if ($attribute) {
                $this->priceAttributes[$attribute->getId()] = 'special_price';
            }
            $attribute = $this->eavConfig->getAttribute(Product::ENTITY, 'price_type');
            if ($attribute) {
                $this->priceAttributes[$attribute->getId()] = 'price_type';
            }
        }
        if (!$this->priceAttributes) {
            throw new UnableRetrieveData('Price attributes not found');
        }
        return $this->priceAttributes;
    }

    /**
     * Resolve price code
     *
     * @param array $row
     * @return string
     * @throws UnableRetrieveData|LocalizedException
     */
    private function resolvePriceCode(array $row): string
    {
        return $this->getPriceAttributes()[$row['attributeId']] ?? self::UNKNOWN_PRICE_CODE;
    }

    /**
     * Add Customer Group Prices
     *
     * @param array $prices
     * @param array $productIds
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    private function addCustomerGroupPrices(array &$prices, array $productIds): void
    {
        $cursor = $this->resourceConnection->getConnection()
            ->query($this->customerGroupPricesQuery->getQuery($productIds));
        while ($row = $cursor->fetch()) {
            $customerGroupId = $row['customer_group_id'];
            $key = $this->buildKey($row['entity_id'], $row['website_id'], $customerGroupId . $row['all_groups']);
            $keyFallback = $this->buildKey($row['entity_id'], $row['website_id'], self::FALLBACK_CUSTOMER_GROUP);
            $fallbackPrice = $prices[$keyFallback] ?? null;
            if (!$fallbackPrice) {
                $this->logger->error('Fallback price not found when adding customer group' . var_export($row, true));
                continue;
            }

            $priceValue = $row['value'] ?? null;
            $pricePercentage = $row['percentage_value'] ?? null;

            // add "group" price to fallback price
            if ((int)$row['all_groups'] === 1) {
                $this->addDiscountPrice($prices[$keyFallback], 'group', $priceValue, $pricePercentage);
                continue;
            }
            // copy feed data from fallbackPrice for each row of customer group price
            $prices[$key] = $fallbackPrice;

            // override customer group specific fields
            $this->addDiscountPrice($prices[$key], 'group', $priceValue, $pricePercentage, true);
            $prices[$key]['customerGroupCode'] = $this->buildCustomerGroupCode($customerGroupId);
            $prices[$key]['productPriceId'] = $key;
        }
    }

    /**
     * Add Catalog Rule Prices
     *
     * @param array $prices
     * @param array $productIds
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    private function addCatalogRulePrices(array &$prices, array $productIds): void
    {
        $cursor = $this->resourceConnection->getConnection()
            ->query($this->catalogRulePricesQuery->getQuery($productIds));
        while ($row = $cursor->fetch()) {
            $customerGroupId = $row['customer_group_id'];
            $key = $this->buildKey(
                $row['entity_id'],
                $row['website_id'],
                $customerGroupId . self::PRICE_SCOPE_KEY_PART
            );

            $keyFallback = $this->buildKey($row['entity_id'], $row['website_id'], self::FALLBACK_CUSTOMER_GROUP);
            $fallbackPrice = $prices[$keyFallback] ?? null;
            if (!$fallbackPrice) {
                $this->logger->error('Fallback price not found when adding catalog rule' . var_export($row, true));
                continue;
            }

            // copy feed data from fallbackPrice for each row of customer group price
            if (empty($prices[$key])) {
                $prices[$key] = $fallbackPrice;
            }

            $this->addDiscountPrice($prices[$key], 'catalog_rule', $row['value']);
            $prices[$key]['customerGroupCode'] = sha1($customerGroupId);
            $prices[$key]['productPriceId'] = $key;
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
     * @param array $prices
     * @param string $code
     * @param ?string $price
     * @param ?string $percentage
     * @param bool $override
     * @return void
     */
    private function addDiscountPrice(
        array &$prices,
        string $code,
        string $price = null,
        string $percentage = null,
        bool $override = false
    ): void {
        if ($override) {
            foreach ($prices['discounts'] as &$discount) {
                if ($discount['code'] === $code) {
                    $this->setPriceOrPercentageDiscount($discount, $price, $percentage);
                    return;
                }
            }
        }
        unset($discount);

        if (null === $price && null === $percentage) {
            return;
        }

        $priceDiscount['code'] = $code;
        $this->setPriceOrPercentageDiscount($priceDiscount, $price, $percentage);
        $prices['discounts'][] = $priceDiscount;
    }

    /**
     * Fill Output
     *
     * @param array $row
     * @param string $key
     * @return array
     */
    private function fillOutput(array $row, string $key): array
    {
        $parentsRaw = !empty($row['parent_skus']) ? explode("{\0}", $row['parent_skus']) : [];
        $parents = [];
        foreach ($parentsRaw as $parent) {
            $parentTypeWithSkuPair = explode("*\0*", $parent, 2);
            if (!isset($parentTypeWithSkuPair[0], $parentTypeWithSkuPair[1])) {
                $this->logger->error(
                    'Parent SKU has illegal NUL symbol and cannot be exported',
                    ['parentSku' => $parent, 'row' => $row]
                );
                continue;
            } else {
                $parentType = $parentTypeWithSkuPair[0];
                $parentSku = $parentTypeWithSkuPair[1];
            }
            $parents[] = [
                'type' => $this->convertProductType($parentType),
                'sku' => $parentSku,
            ];
        }

        return [
            // system fields required for handle product / website deletion
            'websiteId' => $row['website_id'],
            'productId' => $row['entity_id'],

            // feed fields
            'sku' => $row['sku'],
            'type' => $this->convertProductType($row['type_id']),
            'websiteCode' => $row['websiteCode'],
            'updatedAt' => $this->dateTime->formatDate(time()),
            'customerGroupCode' => self::FALLBACK_CUSTOMER_GROUP,
            'parents' => !empty($parents) ? $parents : null,
            'discounts' => [],
            'deleted' => false,
            'productPriceId' => $key,
        ];
    }

    /**
     * Convert Product Type
     *
     * @param string $typeId
     * @return string
     */
    private function convertProductType(string $typeId): string
    {
        $productType = in_array($typeId, self::PRODUCT_TYPE, true) ? $typeId : Type::TYPE_SIMPLE;

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

    /**
     * Delete items from the feed
     *
     * @param array $output
     * @return void
     */
    private function setItemsToDelete(array &$output): void
    {
        $feedItemsToDelete = $this->deleteFeedItems->execute($output);
        foreach ($feedItemsToDelete as $item) {
            $key = $this->buildKey($item['productId'], $item['websiteId'], $item['customerGroupCode']);
            $item['productPriceId'] = $key;
            $output[$key] = $item;
        }
    }
}
