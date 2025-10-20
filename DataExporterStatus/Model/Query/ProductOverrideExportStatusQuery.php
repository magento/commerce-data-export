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

use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\ProductOverrideDataExporter\Model\ViewTableMaintainer;
use Magento\Store\Model\ScopeInterface;

/**
 * Query for product override export status
 */
class ProductOverrideExportStatusQuery implements ExportStatusQueryInterface
{
    /**
     * @param ResourceConnection $resourceConnection
     * @param ScopeConfigInterface $scopeConfig
     * @param ViewTableMaintainer $viewTableMaintainer
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ViewTableMaintainer $viewTableMaintainer,
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getSourceRecordsQty(FeedIndexMetadata $feedIndexMetadata): int
    {
        $categoryPermissionsEnabled = $this->scopeConfig->getValue(
            ConfigInterface::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE
        );

        if ($categoryPermissionsEnabled) {
            $connection = $this->resourceConnection->getConnection();
            if ($this->viewTableMaintainer->isDimensionModeEnabled()) {
                $select = $connection->select()
                    ->from(
                        ['product' => $this->resourceConnection->getTableName(
                            $feedIndexMetadata->getSourceTableName()
                        )],
                        ['qty' => new \Zend_Db_Expr(
                            'COUNT(DISTINCT product.entity_id, website.website_id, combined.customer_group_id)'
                        )]
                    )
                    ->joinCross(
                        ['store' => $this->resourceConnection->getTableName('store')]
                    )
                    ->joinInner(
                        ['website' => $this->resourceConnection->getTableName('store_website')],
                        'website.website_id = store.website_id'
                    )
                    ->joinInner(
                        ['combined' => $connection->select()
                            ->union(array_map(
                                fn($tableName) => $connection->select()
                                    ->from(
                                        $tableName,
                                        ['product_id', 'store_id', 'customer_group_id']
                                    ),
                                $this->viewTableMaintainer->getDimensionTables()
                            ))
                        ],
                        'combined.product_id = product.entity_id AND combined.store_id = store.store_id',
                    );
            } else {
                $select = $connection->select()
                    ->from(
                        ['product' => $this->resourceConnection->getTableName(
                            $feedIndexMetadata->getSourceTableName()
                        )],
                        ['qty' => new \Zend_Db_Expr(
                            'COUNT(DISTINCT product.entity_id, website.website_id, permission.customer_group_id)'
                        )]
                    )
                    ->joinInner(
                        ['permission' => $this->resourceConnection->getTableName(
                            'magento_catalogpermissions_index_product'
                        )],
                        'permission.product_id = product.entity_id'
                    )
                    ->joinInner(
                        ['store' => $this->resourceConnection->getTableName('store')],
                        'store.store_id = permission.store_id'
                    )
                    ->joinInner(
                        ['website' => $this->resourceConnection->getTableName('store_website')],
                        'website.website_id = store.website_id'
                    );
            }
            return (int) $connection->fetchOne($select);
        }

        return 0;
    }
}
