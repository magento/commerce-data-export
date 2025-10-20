<?php
/**
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2025 Adobe
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

namespace Magento\DataExporterStatus\Model\Query;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\Expression;
use Magento\Store\Model\Store;

/**
 * Query for category export status
 */
class CategoryExportStatusQuery implements ExportStatusQueryInterface
{
    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
    ) {
    }

    /**
     * @inheritdoc
     */
    private function getSource(FeedIndexMetadata $feedIndexMetadata): Select
    {
        $connection = $this->resourceConnection->getConnection();
        return $connection->select()
            ->from(
                ['category' => $this->resourceConnection->getTableName($feedIndexMetadata->getSourceTableName())],
                ['qty' => new \Zend_Db_Expr('COUNT(DISTINCT category.entity_id, store.code)')]
            )
            ->joinCross(
                ['store' => $this->resourceConnection->getTableName('store')]
            )
            ->join(
                ['store_group' => $this->resourceConnection->getTableName('store_group')],
                'store.group_id = store_group.group_id'
            )
            ->where('store.store_id != ?', Store::DEFAULT_STORE_ID)
            ->where(
                \sprintf(
                    'category.path LIKE %s or category.path LIKE %s',
                    new Expression("CONCAT('%/', store_group.root_category_id, '/%')"),
                    new Expression("CONCAT('%/', store_group.root_category_id)")
                )
            );
    }

    /**
     * @inheritdoc
     */
    public function getSourceRecordsQty(FeedIndexMetadata $feedIndexMetadata): int
    {
        $connection = $this->resourceConnection->getConnection();
        return (int)$connection->fetchOne($this->getSource($feedIndexMetadata));
    }
}
