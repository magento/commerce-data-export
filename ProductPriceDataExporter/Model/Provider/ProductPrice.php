<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductPriceDataExporter\Model\Provider;

use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
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

    // mapping for ProductPriceAggregate.type
    public const PRODUCT_TYPE_DEFAULT = 0;
    public const PRODUCT_TYPE_CONFIGURABLE = 1;
    public const PRODUCT_TYPE_BUNDLE = 2;

    private const PRODUCT_TYPE_MAPPING = [
        Type::TYPE_SIMPLE => self::PRODUCT_TYPE_DEFAULT,
        Type::TYPE_VIRTUAL => self::PRODUCT_TYPE_DEFAULT,
        Configurable::TYPE_CODE => self::PRODUCT_TYPE_CONFIGURABLE,
        Grouped::TYPE_CODE => self::PRODUCT_TYPE_CONFIGURABLE,
        Type::TYPE_BUNDLE => self::PRODUCT_TYPE_BUNDLE,
//        \Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::TYPE_GIFTCARD => self::PRODUCT_TYPE_DEFAULT,
    ];

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ProductPricesQuery
     */
    private $pricesQuery;

    /**
     * @var CustomerGroupPricesQuery
     */
    private $customerGroupPricesQuery;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var CatalogRulePricesQuery
     */
    private $catalogRulePricesQuery;

    public function __construct(
        ProductPricesQuery       $pricesQuery,
        CustomerGroupPricesQuery $customerGroupPricesQuery,
        CatalogRulePricesQuery   $catalogRulePricesQuery,
        ResourceConnection       $resourceConnection,
        DateTime                 $dateTime
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->pricesQuery = $pricesQuery;
        $this->customerGroupPricesQuery = $customerGroupPricesQuery;
        $this->dateTime = $dateTime;
        $this->catalogRulePricesQuery = $catalogRulePricesQuery;
    }

    /**
     * @param array $values
     * @return array
     * @throws \Zend_Db_Statement_Exception
     * @throws \Magento\DataExporter\Exception\UnableRetrieveData
     */
    public function get(array $values): array
    {
        $ids = \array_column($values, 'productId');
        $cursor = $this->resourceConnection->getConnection()->query($this->pricesQuery->getQuery($ids));
        $output = [];
        while ($row = $cursor->fetch()) {
            $key = $this->buildKey($row['entity_id'], $row['website_id'], self::FALLBACK_CUSTOMER_GROUP);
            if (!isset($output[$key])) {
                $output[$key] = $this->fillOutput($row, $key);
            }
            if ($row['price_attribute'] === self::REGULAR_PRICE) {
                $output[$key]['regular'] = $row['price'];
            } else {
                $this->addDiscountPrice($output[$key], $row['price_attribute'], (float)$row['price']);
            }
        }
        $this->addCustomerGroupPrices($output, $ids);
        $this->addCatalogRulePrices($output, $ids);
        return $output;
    }

    private function addCustomerGroupPrices(array &$prices, array $productIds): void
    {
        $cursor = $this->resourceConnection->getConnection()
            ->query($this->customerGroupPricesQuery->getQuery($productIds));
        while ($row = $cursor->fetch()) {
            $customerGroupId = $row['customer_group_id'];
            $key = $this->buildKey($row['entity_id'], $row['website_id'], $customerGroupId . $row['all_groups']);
            $keyFallback = $this->buildKey($row['entity_id'], $row['website_id'], self::FALLBACK_CUSTOMER_GROUP);
            $fallbackPrice = $prices[$keyFallback] ?? null;
            // TODO: log error if no fallback price found
            if (!$fallbackPrice) {
                throw new UnableRetrieveData('Fallback price not found ...' . var_export($row, true));
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
     * @param array $prices
     * @param array $productIds
     * @return void
     * @throws UnableRetrieveData
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
            // TODO: log error if no fallback price found
            if (!$fallbackPrice) {
                throw new UnableRetrieveData('Fallback price not found ...' . var_export($row, true));
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
     * @param string $productId
     * @param string $websiteId
     * @param string $customerGroup
     * @return string
     */
    private function buildKey(string $productId, string $websiteId, string $customerGroup): string
    {
        return $productId . $websiteId . $customerGroup;
    }

    /**
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
     * @param array $row
     * @param string $key
     * @return array
     */
    private function fillOutput(array $row, string $key): array
    {
        $parentsRaw = !empty($row['parent_skus']) ? \explode(',', $row['parent_skus']) : [];
        $parents = [];
        foreach ($parentsRaw as $parent) {
            // TODO: split by "<type1|type2>:.*>" to handle case when sku contains ":"
            list($parentType, $parentSku) = \explode(':', $parent);
            $parents[] = [
                'type' => $this->convertProductType($parentType),
                'sku' => $parentSku
            ];
        }

        return [
            // system fields required for handle product / website deletion
            'websiteId' => $row['website_id'],
            'productId' => $row['entity_id'],

            // feed fields
            'sku' => $row['sku'],
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
     * @param string $typeId
     * @return string
     */
    private function convertProductType(string $typeId): string
    {
        $productType = self::PRODUCT_TYPE_MAPPING[$typeId] ?? self::PRODUCT_TYPE_DEFAULT;

        return strtoupper((string)$productType);
    }

    /**
     * Build customer group code from the customer group id.
     * Using sha1 to generate the code
     *
     * @param $customerGroupId
     * @return string
     */
    private function buildCustomerGroupCode($customerGroupId): string
    {
        return sha1($customerGroupId);
    }
}
