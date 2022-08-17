<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product;

use Magento\CatalogDataExporter\Model\Query\ProductPriceQuery;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * Product prices data provider
 */
class Prices
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ProductPriceQuery
     */
    private $productPriceQuery;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Prices constructor.
     *
     * @param ResourceConnection $resourceConnection
     * @param ProductPriceQuery $productPriceQuery
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ProductPriceQuery $productPriceQuery,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->productPriceQuery = $productPriceQuery;
        $this->logger = $logger;
    }

    /**
     * Format provider data
     *
     * @param array $row
     * @return array
     */
    private function format(array $row) : array
    {
        $output = [
            'productId' => $row['productId'],
            'storeViewCode' => $row['storeViewCode'],
            'prices' => [
                'minimumPrice' => [
                    'regularPrice' => (float)$row['price'] === 0.0 && $row['min_price'] !== 0.0
                        ? $row['min_price']
                        : $row['price'],
                    'finalPrice' => (float)$row['final_price'] === 0.0 && $row['min_price'] !== 0.0
                        ? $row['min_price']
                        : $row['final_price']
                ],
                'maximumPrice' => [
                    'regularPrice' => $row['max_price'],
                    'finalPrice' => $row['max_price']
                ]
            ]
        ];
        return $output;
    }

    /**
     * Get provider data
     *
     * @param array $values
     * @return array
     * @throws UnableRetrieveData
     */
    public function get(array $values) : array
    {
        $connection = $this->resourceConnection->getConnection();
        $queryArguments = [];
        try {
            $output = [];
            foreach ($values as $value) {
                $queryArguments['productId'][$value['productId']] = $value['productId'];
                $queryArguments['storeViewCode'][$value['storeViewCode']] = $value['storeViewCode'];
            }
            $select = $this->productPriceQuery->getQuery($queryArguments);
            $cursor = $connection->query($select);
            while ($row = $cursor->fetch()) {
                $output[] = $this->format($row);
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            throw new UnableRetrieveData('Unable to retrieve price data');
        }
        return $output;
    }
}
