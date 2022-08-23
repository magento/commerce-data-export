<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\BundleProductDataExporter\Model\Provider\Product;

use Magento\BundleProductDataExporter\Model\Query\BundleProductOptionsQuery;
use Magento\BundleProductDataExporter\Model\Query\BundleProductOptionValuesQuery;
use Magento\CatalogDataExporter\Model\Provider\Product\OptionProviderInterface;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;
use Throwable;

/**
 * Class which provides bundle product options and option values
 */
class BundleProductOptions implements OptionProviderInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var BundleProductOptionsQuery
     */
    private $bundleProductOptionsQuery;

    /**
     * @var BundleProductOptionValuesQuery
     */
    private $bundleProductOptionValuesQuery;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var BundleItemOptionUid
     */
    private $bundleItemOptionUid;

    /**
     * @param ResourceConnection $resourceConnection
     * @param BundleProductOptionsQuery $bundleProductOptionsQuery
     * @param BundleProductOptionValuesQuery $bundleProductOptionValuesQuery
     * @param BundleItemOptionUid $bundleItemOptionUid
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        BundleProductOptionsQuery $bundleProductOptionsQuery,
        BundleProductOptionValuesQuery $bundleProductOptionValuesQuery,
        BundleItemOptionUid $bundleItemOptionUid,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->bundleProductOptionsQuery = $bundleProductOptionsQuery;
        $this->bundleProductOptionValuesQuery = $bundleProductOptionValuesQuery;
        $this->logger = $logger;
        $this->bundleItemOptionUid = $bundleItemOptionUid;
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
                $optionValues = $this->getOptionValues($productIds, $storeViewCode);
                $cursor = $this->resourceConnection->getConnection()->query(
                    $this->bundleProductOptionsQuery->getQuery($productIds, $storeViewCode)
                );

                while ($row = $cursor->fetch()) {
                    $output[] = $this->formatBundleOptionsRow($row, $optionValues);
                }
            }
        } catch (Throwable $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            throw new UnableRetrieveData('Unable to retrieve bundle product options data');
        }

        return $output;
    }

    /**
     * Get bundle products option values
     *
     * @param int[] $productIds
     * @param string $storeViewCode
     *
     * @return array
     */
    private function getOptionValues(array $productIds, string $storeViewCode) : array
    {
        $output = [];

        $cursor = $this->resourceConnection->getConnection()->query(
            $this->bundleProductOptionValuesQuery->getQuery($productIds, $storeViewCode)
        );

        while ($row = $cursor->fetch()) {
            $output[$row['parent_id']][$row['option_id']][] = $this->formatBundleValuesRow($row);
        }

        return $output;
    }

    /**
     * Format bundle item options row
     *
     * @param array $row
     * @param array $optionValues
     *
     * @return array
     */
    private function formatBundleOptionsRow(array $row, array $optionValues) : array
    {
        return [
            'productId' => $row['product_id'],
            'storeViewCode' => $row['store_view_code'],
            'optionsV2' => [
                'type' => BundleItemOptionUid::OPTION_TYPE,
                'id' => $row['option_id'],
                'renderType' => $row['render_type'],
                'required' => $row['required'],
                'label' => $row['label'],
                'sortOrder' => $row['sort_order'],
                'values' => $optionValues[$row['parent_id']][$row['option_id']] ?? [],
            ],
        ];
    }

    /**
     * Format bundle item values row
     *
     * @param array $row
     *
     * @return array
     */
    private function formatBundleValuesRow(array $row) : array
    {
        return [
            'id' => $this->bundleItemOptionUid->resolve($row['option_id'], $row['id'], $row['qty']),
            'sku' => $row['sku'],
            'label' => $row['label'],
            'qty' => $row['qty'],
            'sortOrder' => $row['sort_order'],
            'isDefault' => $row['default'],
            'qtyMutability' => (bool)$row['qty_mutability'],
        ];
    }
}
