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

use Magento\Catalog\Model\Product\LinkTypeProvider;
use Magento\CatalogDataExporter\Model\Query\ProductLinksQuery;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * Product links data provider
 */
class Links
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ProductLinksQuery
     */
    private $productLinksQuery;

    /**
     * @var LinkTypeProvider
     */
    private $linkTypeProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ProductLinksQuery $productLinksQuery
     * @param LinkTypeProvider $linkTypeProvider
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ProductLinksQuery $productLinksQuery,
        LinkTypeProvider $linkTypeProvider,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->productLinksQuery = $productLinksQuery;
        $this->linkTypeProvider = $linkTypeProvider;
        $this->logger = $logger;
    }

    /**
     * Get provider data
     *
     * @param array $values
     *
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
            $linkTypes = \array_flip($this->linkTypeProvider->getLinkTypes());

            foreach ($queryArguments as $storeViewCode => $productIds) {
                $cursor = $this->resourceConnection->getConnection()->query(
                    $this->productLinksQuery->getQuery($productIds, $storeViewCode)
                );

                while ($row = $cursor->fetch()) {
                    $output[] = [
                        'productId' => $row['parentId'],
                        'storeViewCode' => $storeViewCode,
                        'links' => $this->formatLinkRow($row, $linkTypes),
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
     * Format link row data.
     *
     * @param array $row
     * @param array $linkTypes
     *
     * @return array
     */
    private function formatLinkRow(array $row, array $linkTypes) : array
    {
        return [
            'sku' => $row['sku'],
            'type' => $linkTypes[$row['link_type_id']] ?? null
        ];
    }
}
