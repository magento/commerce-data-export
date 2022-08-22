<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GroupedProductDataExporter\Model\Provider\Product;

use Magento\CatalogDataExporter\Model\Provider\Product\OptionProviderInterface;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\GroupedProductDataExporter\Model\Query\GroupedProductOptionsQuery;
use Magento\GroupedProductDataExporter\Model\Query\GroupedProductOptionValuesQuery;
use Throwable;

/**
 * Class which provides grouped product options and option values
 */
class GroupedProductOptions implements OptionProviderInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var GroupedProductOptionsQuery
     */
    private $groupedProductOptionsQuery;

    /**
     * @var GroupedProductOptionValuesQuery
     */
    private $groupedProductOptionValuesQuery;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param GroupedProductOptionsQuery $groupedProductOptionsQuery
     * @param GroupedProductOptionValuesQuery $groupedProductOptionValuesQuery
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        GroupedProductOptionsQuery $groupedProductOptionsQuery,
        GroupedProductOptionValuesQuery $groupedProductOptionValuesQuery,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->groupedProductOptionsQuery = $groupedProductOptionsQuery;
        $this->groupedProductOptionValuesQuery = $groupedProductOptionValuesQuery;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function get(array $values) : array
    {
        $output = [];
        $queryArguments = [];
        $productIds = [];

        foreach ($values as $value) {
            $productIds[$value['productId']] = $value['productId'];
            $queryArguments[$value['storeViewCode']][$value['productId']] = $value['productId'];
        }

        try {
            foreach ($queryArguments as $storeViewCode => $productIds) {
                $optionValues = $this->getOptionValues($productIds);
                $cursor = $this->resourceConnection->getConnection()->query(
                    $this->groupedProductOptionsQuery->getQuery($productIds, $storeViewCode)
                );

                while ($row = $cursor->fetch()) {
                    $output[] = $this->formatGroupedOptionsRow($row, $optionValues);
                }
            }
        } catch (Throwable $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            throw new UnableRetrieveData('Unable to retrieve grouped product options data');
        }

        return $output;
    }

    /**
     * Get grouped products option values
     *
     * @param int[] $productIds
     *
     * @return array
     */
    private function getOptionValues(array $productIds) : array
    {
        $output = [];

        $cursor = $this->resourceConnection->getConnection()->query(
            $this->groupedProductOptionValuesQuery->getQuery($productIds)
        );

        while ($row = $cursor->fetch()) {
            $output[$row['parent_id']][] = $this->formatGroupedValuesRow($row);
        }

        return $output;
    }

    /**
     * Format grouped item options row
     *
     * @param array $row
     * @param array $optionValues
     *
     * @return array
     */
    private function formatGroupedOptionsRow(array $row, array $optionValues) : array
    {
        return [
            'productId' => $row['product_id'],
            'storeViewCode' => $row['store_view_code'],
            'optionsV2' => [
                'type' => Grouped::TYPE_CODE,
                'required' => true,
                'sortOrder' => 1,
                'values' => $optionValues[$row['product_id']] ?? [],
            ],
        ];
    }

    /**
     * Format grouped item values row
     *
     * @param array $row
     *
     * @return array
     */
    private function formatGroupedValuesRow(array $row) : array
    {
        return [
            'id' => $row['id'],
            'sku' => $row['sku'],
            'qty' => $row['qty'],
            'sortOrder' => $row['sort_order'],
            'label' => null,
            'isDefault' => true,
            'qtyMutability' => false,
        ];
    }
}
