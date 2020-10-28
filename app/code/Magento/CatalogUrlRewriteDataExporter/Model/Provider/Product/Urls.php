<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogUrlRewriteDataExporter\Model\Provider\Product;

use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\CatalogUrlRewriteDataExporter\Model\Query\ProductUrlQuery;
use Magento\Store\Api\StoreConfigManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Options
 */
class Urls
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ProductUrlQuery
     */
    private $productUrlQuery;

    /**
     * @var StoreConfigManagerInterface
     */
    private $storeConfigManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Urls constructor.
     *
     * @param ResourceConnection $resourceConnection
     * @param ProductUrlQuery $productUrlQuery
     * @param StoreConfigManagerInterface $storeConfigManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ProductUrlQuery $productUrlQuery,
        StoreConfigManagerInterface $storeConfigManager,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->productUrlQuery = $productUrlQuery;
        $this->storeConfigManager = $storeConfigManager;
        $this->logger = $logger;
    }

    /**
     * Get provider data
     *
     * @param array $values
     * @return array
     * @throws UnableRetrieveData
     * @throws \Zend_Db_Statement_Exception
     */
    public function get(array $values) : array
    {
        $queryArguments = [];
        try {
            $output = [];
            foreach ($values as $value) {
                $queryArguments['productId'][$value['productId']] = $value['productId'];
                $queryArguments['storeViewCode'][$value['storeViewCode']] = $value['storeViewCode'];
            }
            $select = $this->productUrlQuery->getQuery($queryArguments);
            $connection = $this->resourceConnection->getConnection();
            $baseUrls = [];
            foreach ($this->storeConfigManager->getStoreConfigs($queryArguments['storeViewCode']) as $config) {
                $baseUrls[$config->getCode()] = $config->getBaseUrl();
            }
            $cursor = $connection->query($select);
            while ($row = $cursor->fetch()) {
                $row['url'] = $baseUrls[$row['storeViewCode']] . $row['url'];
                $output[] = $row;
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve product URL data');
        }
        return $output;
    }
}
