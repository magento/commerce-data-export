<?php
/**
 * Copyright 2023 Adobe
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
     * @param ?string $storeViewCode
     * @param bool $addOptionValues
     * @return array
     * @throws NoSuchEntityException
     */
    public function get(
        array $productIds,
        array $optionTypes,
        string $storeViewCode = null,
        bool $addOptionValues = true
    ): array {
        $connection = $this->resourceConnection->getConnection();
        $filteredProductOptions = [];
        $productOptionsSelect = $this->customOptions->query([
            'product_ids' => $productIds,
            'storeViewCode' => $storeViewCode
        ]);
        $productOptions = $connection->fetchAssoc($productOptionsSelect);
        if (true === $addOptionValues) {
            $productOptions = $this->addValues($productOptions, $storeViewCode);
        }

        foreach ($productOptions as $optionId => $option) {
            if (\in_array($option['type'], $optionTypes, true)) {
                $filteredProductOptions[$option['entity_id']][$optionId] = $option;
            }
        }

        return $filteredProductOptions;
    }

    /**
     * Adding values to product options array
     *
     * @param array $productOptions
     * @param null|string $storeViewCode
     * @return array
     * @throws NoSuchEntityException
     */
    private function addValues(array $productOptions, ?string $storeViewCode): array
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
                $productOptions[$optionValue['option_id']]['values'][$optionValue['option_type_id']] = $optionValue;
            }
        }

        return $productOptions;
    }

    /**
     * Get product options values by product ids and store view code
     *
     * @param array $productIds
     * @param array $optionTypes
     * @param ?string $storeViewCode
     * @return array
     * @throws NoSuchEntityException
     */
    public function getValuesByProductIds(
        array $productIds,
        array $optionTypes = [],
        ?string $storeViewCode = null
    ): array {
        $productOptions = [];
        $optionValues = $this->customOptionValues->queryValuesByProductIds(
            [
                'productIds' => $productIds,
                'storeViewCode' => $storeViewCode
            ]
        );
        $optionValues = $this->resourceConnection->getConnection()
            ->fetchAll($optionValues);

        foreach ($optionValues as $optionValue) {
            if (!empty($optionTypes) && !\in_array($optionValue['option_type'], $optionTypes, true)) {
                continue;
            }
            $key = $optionValue['product_id'] . $storeViewCode . $optionValue['option_id'];
            $productOptions[$key][$optionValue['option_type_id']] = $optionValue;
        }

        return $productOptions;
    }
}
