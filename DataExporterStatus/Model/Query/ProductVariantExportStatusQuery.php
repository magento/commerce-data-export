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

/**
 * Query for product variant export status
 */
class ProductVariantExportStatusQuery implements ExportStatusQueryInterface
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

        $joinField = $connection->getAutoIncrementField(
            $this->resourceConnection->getTableName('catalog_product_entity')
        );

        return $connection->select()
            ->from(
                ['product' => $this->resourceConnection->getTableName($feedIndexMetadata->getSourceTableName())],
                ['qty' => new \Zend_Db_Expr('COUNT(DISTINCT product.entity_id)')]
            )
            ->joinInner(
                ['super_link' => $this->resourceConnection->getTableName('catalog_product_super_link')],
                'super_link.product_id = product.entity_id',
                []
            )
            ->joinInner(
                ['parent' => $this->resourceConnection->getTableName($feedIndexMetadata->getSourceTableName())],
                \sprintf('parent.%s = super_link.parent_id', $joinField),
                []
            )
            ->joinInner(
                ['link_attr' => $this->resourceConnection->getTableName(
                    [$feedIndexMetadata->getSourceTableName(), 'int']
                )],
                \sprintf(
                    'link_attr.%1$s = product.%1$s AND link_attr.attribute_id IN (%2$s)',
                    $joinField,
                    $connection->select()
                        ->from(
                            ['super_attr' => $this->resourceConnection->getTableName(
                                'catalog_product_super_attribute'
                            )],
                            ['attribute_id']
                        )
                        ->where(\sprintf('super_attr.product_id IN (parent.%s)', $joinField))
                ),
                []
            )
            ->joinInner(
                ['eav_attr' => $this->resourceConnection->getTableName('eav_attribute')],
                'eav_attr.attribute_id = link_attr.attribute_id',
                []
            );
    }

    /**
     * @inheritdoc
     */
    public function getSourceRecordsQty(FeedIndexMetadata $feedIndexMetadata): int
    {
        $connection = $this->resourceConnection->getConnection();
        return (int) $connection->fetchOne($this->getSource($feedIndexMetadata));
    }
}
