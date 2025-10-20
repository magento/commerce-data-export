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

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Store\Model\Store;

/**
 * Query for product attribute export status
 */
class ProductAttributeExportStatusQuery implements ExportStatusQueryInterface
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
                ['eav' => $this->resourceConnection->getTableName($feedIndexMetadata->getSourceTableName())],
                ['qty' => new \Zend_Db_Expr('COUNT(DISTINCT eav.attribute_id, store.store_id)')]
            )
            ->joinCross(
                ['store' => $this->resourceConnection->getTableName('store')]
            )
            ->join(
                ['eav_type' => $this->resourceConnection->getTableName('eav_entity_type')],
                sprintf(
                    'eav_type.entity_type_code = "%s" AND eav.entity_type_id = eav_type.entity_type_id',
                    ProductAttributeInterface::ENTITY_TYPE_CODE
                )
            )
            ->join(
                ['cea' => $this->resourceConnection->getTableName('catalog_eav_attribute')],
                'eav.attribute_id = cea.attribute_id'
            )
            ->where('store.store_id != ?', Store::DEFAULT_STORE_ID);
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
