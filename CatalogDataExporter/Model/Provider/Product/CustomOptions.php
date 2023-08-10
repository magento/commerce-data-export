<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product;

use Magento\CatalogDataExporter\Model\Query\CustomOptions as CustomOptionsQuery;
use Magento\CatalogDataExporter\Model\Query\CustomOptionValues;
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
     * @param CustomOptionsQuery $customOptions
     * @param CustomOptionValues $customOptionValues
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        CustomOptionsQuery $customOptions,
        CustomOptionValues $customOptionValues,
        ResourceConnection $resourceConnection,
    ) {
        $this->customOptions = $customOptions;
        $this->resourceConnection = $resourceConnection;
        $this->customOptionValues = $customOptionValues;
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

        foreach ($productOptions as $option) {
            if (in_array($option['type'], $optionTypes)) {
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
}
