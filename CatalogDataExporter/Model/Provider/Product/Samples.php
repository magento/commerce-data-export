<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product;

use Magento\CatalogDataExporter\Model\Provider\Product\Downloadable\SampleUrlProvider;
use Magento\CatalogDataExporter\Model\Query\ProductSamplesQuery;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * Product samples data provider
 */
class Samples
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ProductSamplesQuery
     */
    private $productSamplesQuery;

    /**
     * @var SampleUrlProvider
     */
    private $sampleUrlProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ProductSamplesQuery $productSamplesQuery
     * @param SampleUrlProvider $sampleUrlProvider
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ProductSamplesQuery $productSamplesQuery,
        SampleUrlProvider $sampleUrlProvider,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->productSamplesQuery = $productSamplesQuery;
        $this->sampleUrlProvider = $sampleUrlProvider;
        $this->logger = $logger;
    }

    /**
     * Format provider data
     *
     * @param array $row
     * @param string $storeViewCode
     *
     * @return array
     *
     * @throws NoSuchEntityException
     */
    private function format(array $row, string $storeViewCode): array
    {
        return [
            'productId' => $row['entity_id'],
            'storeViewCode' => $storeViewCode,
            'samples' => [
                'resource' => [
                    'url' => $this->sampleUrlProvider->getBaseSampleUrlByStoreViewCode($storeViewCode)
                        . $row['sample_id'],
                    'label' => $row['title'],
                ],
                'sortOrder' => $row['sort_order']
            ]
        ];
    }

    /**
     * Get provider data
     *
     * @param array $values
     * @return array
     * @throws NoSuchEntityException
     * @throws \Zend_Db_Statement_Exception
     * @throws \Exception
     */
    public function get(array $values): array
    {
        $output = [];
        $queryArguments = [];

        foreach ($values as $value) {
            $queryArguments[$value['storeViewCode']][$value['productId']] = $value['productId'];
        }

        try {
            $connection = $this->resourceConnection->getConnection();

            foreach ($queryArguments as $storeViewCode => $productIds) {
                $select = $this->productSamplesQuery->getQuery($productIds, $storeViewCode);
                $cursor = $connection->query($select);

                while ($row = $cursor->fetch()) {
                    $output[] = $this->format($row, $storeViewCode);
                }
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            throw new UnableRetrieveData('Unable to retrieve product samples data');
        }

        return $output;
    }
}
