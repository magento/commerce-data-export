<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductPriceDataExporter\Model\Provider;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

class ProductCustomerGroup
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    private function getTable(string $tableName): string
    {
        return $this->resourceConnection->getTableName($tableName);
    }

    /**
     * @param string $websiteId
     * @param array $entitiesIds
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function getProductGroups(string $websiteId, array $entitiesIds): array
    {
        $connection = $this->resourceConnection->getConnection();
        $cursor = $connection->query($this->getSelect($websiteId, $entitiesIds));
        $output = [];
        while ($row = $cursor->fetch()) {
            $output[$row['entity_id']] += $row['customer_group_id'];
        }
        return $output;
    }

    /**
     * @param string $websiteId
     * @param array $entitiesIds
     * @return Select
     */
    private function getSelect(string $websiteId, array $entitiesIds): Select
    {
        $connection = $this->resourceConnection->getConnection();
        $using = $connection->getAutoIncrementField($this->getTable('catalog_product_entity'));
        return $connection->select()
            ->from(['cpe' => $this->getTable('catalog_product_entity'), ['cpe.entity_id']])
            ->join(
                ['ctp' => $this->getTable('catalog_product_entity_tier_price')],
                sprintf('cpe.%1$s = ctp.%1$s', $using),
                ['ctp.customer_group_id']
            )
            ->where('ctp.website_id = ?', $websiteId)
            ->where('cpe.entity_id IN (?)', $entitiesIds);
    }
}
