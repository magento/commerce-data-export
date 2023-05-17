<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductPriceDataExporter\Model\Provider;

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
 */
class ProductPrice
{
    private const FALLBACK_CUSTOMER_GROUP = "0";
    private const PRICE_SCOPE_KEY_PART = 0;
    private const REGULAR_PRICE = 'price';
    private const UNKNOWN_PRICE_CODE = 'unknown';

    /**
     * mapping for ProductPriceAggregate.type
     */
    private const PRODUCT_TYPE = [
        Type::TYPE_SIMPLE,
        Configurable::TYPE_CODE,
        Type::TYPE_BUNDLE,
        Downloadable::TYPE_DOWNLOADABLE,
        Grouped::TYPE_CODE,
        'giftcard', // represet giftcard product type
    ];

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var ProductPricesQuery
     */
    private ProductPricesQuery $pricesQuery;

    /**
     * @var CustomerGroupPricesQuery
     */
    private CustomerGroupPricesQuery $customerGroupPricesQuery;

    /**
     * @var DateTime
     */
    private DateTime $dateTime;

    /**
     * @var CatalogRulePricesQuery
     */
    private CatalogRulePricesQuery $catalogRulePricesQuery;

    /**
     * @var DeleteFeedItems
     */
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
        ProductPricesQuery       $pricesQuery,
        CustomerGroupPricesQuery $customerGroupPricesQuery,
        CatalogRulePricesQuery   $catalogRulePricesQuery,
        ResourceConnection       $resourceConnection,
        DeleteFeedItems          $deleteFeedItems,
        DateTime                 $dateTime,
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
     * @throws UnableRetrieveData
     * @throws LocalizedException
     */
    public function get(array $values): array
    {
        $ids = array_column($values, 'productId');
        $cursor = $this->resourceConnection->getConnection()->query(
            $this->pricesQuery->getQuery($ids, \array_keys($this->getPriceAttributes()))
        );
        $output = [];
        while ($row = $cursor->fetch()) {
            $key = $this->buildKey($row['entity_id'], $row['website_id'], self::FALLBACK_CUSTOMER_GROUP);
            if (!isset($output[$key])) {
                $output[$key] = $this->fillOutput($row, $key);
            }
            $priceAttributeCode = $this->resolvePriceCode($row);
            if ($priceAttributeCode === self::REGULAR_PRICE) {
                $output[$key]['regular'] = $row['price'];
            } else {
                $this->addDiscountPrice($output[$key], $priceAttributeCode, (float)$row['price']);
            }
        }
        $filteredIds = array_unique(array_column($output, 'productId'));
        $this->addCustomerGroupPrices($output, $filteredIds);
        $this->addCatalogRulePrices($output, $filteredIds);

        $this->deleteFeedItems->execute($output);
        return $output;
    }

    /**
     * @return array
     * @throws UnableRetrieveData
     * @throws \Magento\Framework\Exception\LocalizedException
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
        }
        if (!$this->priceAttributes) {
            throw new UnableRetrieveData('Price attributes not found');
        }
        return $this->priceAttributes;
    }

    /**
     * @param array $row
     * @return mixed|string
     * @throws UnableRetrieveData|LocalizedException
     */
    private function resolvePriceCode(array $row)
    {
        return $this->getPriceAttributes()[$row['attributeId']] ?? self::UNKNOWN_PRICE_CODE;
    }
    /**
     * Add Customer Group Prices
     *
     * @param array $prices
     * @param array $productIds
     * @return void
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
                continue ;
            }

            $priceValue = (float)$row['value'];
            //calculate percentage discount if so
            if (empty($priceValue) && !empty($row['percentage_value'])) {
                $priceValue = $this->calculatePercentDiscountValue($fallbackPrice['regular'], $row['percentage_value']);
            }

            // add "group" price to fallback price
            if ((int)$row['all_groups'] === 1) {
                $this->addDiscountPrice($prices[$keyFallback], 'group', $priceValue);
                continue;
            }
            // copy feed data from fallbackPrice for each row of customer group price
            $prices[$key] = $fallbackPrice;

            // override customer group specific fields
            $this->addDiscountPrice($prices[$key], 'group', $priceValue, true);
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
                continue ;
            }

            // copy feed data from fallbackPrice for each row of customer group price
            if (empty($prices[$key])) {
                $prices[$key] = $fallbackPrice;
            }

            // override customer group specific fields
            $this->addDiscountPrice($prices[$key], 'catalog_rule', (float)$row['value'], true);
            $prices[$key]['customerGroupCode'] = sha1($customerGroupId);
            $prices[$key]['productPriceId'] = $key;
        }
    }

    /**
     * Build Key
     *
     * @param string $productId
     * @param string $websiteId
     * @param string $customerGroup
     * @return string
     */
    private function buildKey(string $productId, string $websiteId, string $customerGroup): string
    {
        return implode('-', [$productId, $websiteId, $customerGroup]);
    }

    /**
     * Add Discount Price
     *
     * @param array $prices
     * @param string $code
     * @param float $price
     * @param bool $override
     * @return void
     */
    private function addDiscountPrice(array &$prices, string $code, float $price, bool $override = false): void
    {
        if ($override) {
            foreach ($prices['discounts'] as &$discount) {
                if ($discount['code'] === $code) {
                    $discount['price'] = $price;
                    return;
                }
            }
        }
        $prices['discounts'][] = [
            'code' => $code,
            'price' => $price
        ];
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
        $parentsRaw = !empty($row['parent_skus']) ? explode(',', $row['parent_skus']) : [];
        $parents = [];
        foreach ($parentsRaw as $parent) {
            // TODO: split by "<type1|type2>:.*>" to handle case when sku contains ":"
            [$parentType, $parentSku] = explode(':', $parent);
            $parents[] = [
                'type' => $this->convertProductType(trim($parentType)),
                'sku' => $parentSku
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
            'productPriceId' => $key
        ];
    }

    /**
     * Calculate Percent DiscountValue
     *
     * @param string $price
     * @param string $percent
     * @return float
     */
    private function calculatePercentDiscountValue(string $price, string $percent): float
    {
        $groupDiscountValue = ((float)$percent / 100) * (float)$price;
        return round((float)$price - $groupDiscountValue, 2);
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
}
