<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Query;

use Magento\Catalog\Model\Indexer\Category\Product\TableMaintainer;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class UnavailableProductQuery
 *
 * Class for creating query that get information about product unavailability.
 */
class UnavailableProductQuery
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var TableMaintainer
     */
    private $tableMaintainer;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * UnavailableProductQuery constructor.
     *
     * @param ResourceConnection $resourceConnection
     * @param TableMaintainer $tableMaintainer
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        TableMaintainer $tableMaintainer,
        StoreManagerInterface $storeManager
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->tableMaintainer = $tableMaintainer;
        $this->storeManager = $storeManager;
    }

    /**
     * Get resource table
     *
     * @param string $tableName
     * @return string
     */
    private function getTable(string $tableName): string
    {
        return $this->resourceConnection->getTableName($tableName);
    }

    /**
     * Get query for provider
     *
     * @param array $arguments
     * @return Select
     * @throws NoSuchEntityException
     */
    public function getQuery(array $arguments) : Select
    {
        $productIds = isset($arguments['productId']) ? $arguments['productId'] : [];
        $storeViewCode = isset($arguments['storeViewCode']) ? $arguments['storeViewCode'] : null;
        $store = $this->storeManager->getStore($storeViewCode);
        $connection = $this->resourceConnection->getConnection();
        return $connection->select()
            ->from(
                ['cpe' => $this->getTable('catalog_product_entity')],
                [
                    'productId' => 'cpe.entity_id'
                ]
            )
            ->joinLeft(
                ['ccp' => $this->tableMaintainer->getMainTable((int)$store->getId())],
                'cpe.entity_id = ccp.product_id',
                []
            )
            ->where('ccp.product_id IS NULL')
            ->where('cpe.entity_id IN (?)', $productIds);
    }
}
