<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product\ProductOptions;

use Magento\CatalogDataExporter\Model\Query\ProductLinksQuery;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\GroupedProduct\Model\ResourceModel\Product\Link;

/**
 * Provider class for grouped product options
 */
class GroupedProductOptions implements ProductOptionProviderInterface
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var ProductLinksQuery
     */
    private ProductLinksQuery $productLinksQuery;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ProductLinksQuery $productLinksQuery
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ProductLinksQuery $productLinksQuery,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->productLinksQuery = $productLinksQuery;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     *
     * @param array $values
     * @return array
     *
     * @throws UnableRetrieveData
     */
    public function get(array $values) : array
    {
        $output = [];
        $queryArguments = [];

        foreach ($values as $value) {
            $queryArguments[$value['storeViewCode']][$value['productId']] = $value['productId'];
        }

        try {
             foreach ($queryArguments as $storeViewCode => $productIds) {
                $cursor = $this->resourceConnection->getConnection()->query(
                    $this->productLinksQuery->getQuery($productIds, $storeViewCode, Link::LINK_TYPE_GROUPED)
                );

                while ($row = $cursor->fetch()) {
                    $key = $this->getOptionKey($row['parentId'], $storeViewCode);
                    $optionValues[$key][] = $this->formatOptionsValueRow($row);

                    $output[$key] = [
                        'productId' => $row['parentId'],
                        'storeViewCode' => $storeViewCode,
                        'optionsV2' => [
                            'type' => Grouped::TYPE_CODE,
                            'id' => $row['parentId'],
                            'values' => $optionValues[$key],
                        ]
                    ];
                }
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            throw new UnableRetrieveData('Unable to retrieve product links');
        }

        return $output;
    }

    /**
     * Generate option key by concatenating parentId, storeViewCode
     *
     * @param string $parentId
     * @param string $storeViewCode
     *
     * @return string
     */
    private function getOptionKey(string $parentId, string $storeViewCode): string
    {
        return $parentId . $storeViewCode;
    }

    /**
     * Format options value row data for grouped products.
     *
     * @param array $row
     *
     * @return array
     */
    private function formatOptionsValueRow(array $row) : array
    {
        return [
            'id' => $row['productId'],
            'sku' => $row['sku'],
            'qty' => $row['qty'],
            'sortOrder' => $row['position'],
            'qtyMutability' => true,
        ];
    }
}
