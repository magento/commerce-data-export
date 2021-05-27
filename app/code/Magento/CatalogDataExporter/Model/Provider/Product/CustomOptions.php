<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product;

use Magento\CatalogDataExporter\Model\Query\CustomOptions as CustomOptionsQuery;
use Magento\CatalogDataExporter\Model\Query\CustomOptionValues;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class for fetching product options by type.
 */
class CustomOptions
{
    /**
     * @var CustomOptionsQuery
     */
    private $customOptions;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var CustomOptionValues
     */
    private $customOptionValues;

    /**
     * @var CollectionFactory
     */
    private $customerGroups;

    /**
     * @var string[]
     */
    private $customerGroupsArray = [];

    /**
     * @param CustomOptionsQuery $customOptions
     * @param CustomOptionValues $customOptionValues
     * @param ResourceConnection $resourceConnection
     * @param CollectionFactory $customerGroups
     */
    public function __construct(
        CustomOptionsQuery $customOptions,
        CustomOptionValues $customOptionValues,
        ResourceConnection $resourceConnection,
        CollectionFactory $customerGroups
    ) {
        $this->customOptions = $customOptions;
        $this->resourceConnection = $resourceConnection;
        $this->customOptionValues = $customOptionValues;
        $this->customerGroups = $customerGroups;
    }

    /**
     * Returns product options for by given products ids and filters required options.
     *
     * @param array $productIds
     * @param array $optionTypes
     * @param string $storeViewCode
     * @return array
     * @throws NoSuchEntityException
     */
    public function get(array $productIds, array $optionTypes, string $storeViewCode): array
    {
        $connection = $this->resourceConnection->getConnection();
        $filteredProductOptions = [];
        $productOptionsSelect = $this->customOptions->query([
            'product_ids' => $productIds,
            'storeViewCode' => $storeViewCode
        ]);
        $productOptions = $connection->fetchAssoc($productOptionsSelect);
        $productOptions = $this->addValues($productOptions, $storeViewCode);
        $productOptionsPercentPrices = $this->getPercentFinalPrice($productIds, $storeViewCode);
        // $this->customerGroupsArray = $this->customerGroups->create()->toOptionArray();

        foreach ($productOptions as $option) {
            if (in_array($option['type'], $optionTypes)) {
                $option = $this->setPricingData($option, $productOptionsPercentPrices, $storeViewCode);
                $filteredProductOptions[$option['entity_id']][] = $option;
            }
        }

        return $filteredProductOptions;
    }

    /**
     * Adding values to product options array
     *
     * @param array $productOptions
     * @param string $storeViewCode
     * @return array
     * @throws NoSuchEntityException
     */
    private function addValues(array $productOptions, string $storeViewCode): array
    {
        $optionIds = [];

        foreach ($productOptions as $option) {
            $optionIds[] = $option['option_id'];
        }
        $optionValues = $this->customOptionValues->query(
            [
                'option_ids' => $optionIds,
                'storeViewCode' => $storeViewCode
            ]
        );
        $optionValues = $this->resourceConnection->getConnection()
            ->fetchAll($optionValues);

        foreach ($optionValues as $optionValue) {
            if (isset($productOptions[$optionValue['option_id']])) {
                $productOptions[$optionValue['option_id']]['values'][] = $optionValue;
            }
        }

        return $productOptions;
    }

    /**
     * Get the final product Price
     *
     * @param array $productIds
     * @param string $storeViewCode
     * @return array
     * @throws NoSuchEntityException
     */
    public function getPercentFinalPrice(array $productIds, string $storeViewCode): array
    {
        $formattedPrices = [];
        $priceQuery = $this->customOptionValues->percentPriceQuery($productIds, $storeViewCode);
        $prices = $this->resourceConnection->getConnection()->fetchAll($priceQuery);
        foreach ($prices as $price) {
            $calculatedPrice = $price['price'] / 100 * $price['final_price'];
            $key = $price['entity_id'] . $storeViewCode . $price['option_id'];
            $formattedPrices[$key]['price'] = $calculatedPrice;
        }
        return $formattedPrices;
    }

    /**
     * Fill out the price by type
     *
     * @param array $option
     * @param array $productOptionsPercentPrices
     * @param string $storeViewCode
     * @return array
     */
    private function setPricingData(array $option, array $productOptionsPercentPrices, string $storeViewCode): array
    {
        if ($option['price_type'] === 'percent') {
            $key = $option['entity_id'] . $storeViewCode . $option['option_id'];
            if (isset($productOptionsPercentPrices[$key])) {
                $option['price'] = $productOptionsPercentPrices[$key]['price'];
            }
        } elseif ($option['price_type'] === 'fixed') {
            // TODO: should be handled by ProductOverride feed
            // $prices = [];
            // if (isset($option['price'])) {
            //   foreach ($this->customerGroupsArray as $customerGroup) {
            //        $prices[] = [
            //            'regularPrice' => $option['price'],
            //            'finalPrice' => $option['price'],
            //            'scope' => $customerGroup['label'],
            //        ];
            //    }
            //    $option['price'] = $prices;
            //}
        }

        return $option;
    }
}
