<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogInventoryDataExporter\Model\Plugin;

use Magento\CatalogDataExporter\Model\Provider\Product\Buyable as ProductBuyable;
use Magento\CatalogInventoryDataExporter\Model\Query\CatalogInventoryQuery;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * Plugin for fetching products stock status and marking out of stock products
 */
class Buyable
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CatalogInventoryQuery
     */
    private $catalogInventoryQuery;

    /**
     * @param ResourceConnection $resourceConnection
     * @param CatalogInventoryQuery $catalogInventoryQuery
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        CatalogInventoryQuery $catalogInventoryQuery,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->catalogInventoryQuery = $catalogInventoryQuery;
        $this->logger = $logger;
    }

    /**
     * Check stock status after getting buyable product status
     *
     * @param ProductBuyable $subject
     * @param array $result
     *
     * @return array
     *
     * @throws UnableRetrieveData
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(ProductBuyable $subject, array $result)
    {
        $connection = $this->resourceConnection->getConnection();
        $queryArguments = [];
        try {
            foreach ($result as $value) {
                $queryArguments['productId'][$value['productId']] = $value['productId'];
                $queryArguments['storeViewCode'][$value['storeViewCode']] = $value['storeViewCode'];
            }
            $select = $this->catalogInventoryQuery->getInStock($queryArguments);
            $cursor = $connection->query($select);
            $outOfStock = [];
            while ($row = $cursor->fetch()) {
                if ($row['is_in_stock'] == 0) {
                    $outOfStock[$row['product_id']] = false;
                }
            }
            foreach ($result as &$item) {
                if (isset($outOfStock[$item['productId']])) {
                    $item['buyable'] = false;
                }
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve stock data');
        }
        return $result;
    }
}
