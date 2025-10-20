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

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\ProductPriceDataExporter\Model\Query\DateWebsiteProvider;

/**
 * Query for price export status
 */
class PricesExportStatusQuery implements ExportStatusQueryInterface
{
    /**
     * @param ResourceConnection $resourceConnection
     * @param DateWebsiteProvider $dateWebsiteProvider
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly DateWebsiteProvider $dateWebsiteProvider,
    ) {
    }

    /**
     * @inheritdoc
     */
    private function getSource(FeedIndexMetadata $feedIndexMetadata): Select
    {
        $connection = $this->resourceConnection->getConnection();
        $joinField = $connection->getAutoIncrementField(
            $this->resourceConnection->getTableName($feedIndexMetadata->getSourceTableName())
        );
        return $connection->select()
            ->from(
                $connection->select()
                    ->union([
                        $connection->select()
                            ->from(
                                ['product' => $this->resourceConnection->getTableName(
                                    $feedIndexMetadata->getSourceTableName()
                                )],
                                [
                                    'entity_id' => 'product.entity_id',
                                    'website_id' => 'product_website.website_id',
                                    'customer_group_id' => new \Zend_Db_Expr('0')
                                ]
                            )
                            ->joinInner(
                                ['product_website' => $this->resourceConnection->getTableName(
                                    'catalog_product_website'
                                )],
                                'product_website.product_id = product.entity_id',
                                []
                            )
                            ->joinInner(
                                ['store_website' => $this->resourceConnection->getTableName('store_website')],
                                'store_website.website_id = product_website.website_id',
                                []
                            )
                            ->where('product.type_id NOT IN (?)', [Configurable::TYPE_CODE, 'gift' . 'card']),
                        $connection->select()
                            ->from(
                                ['product' => $this->resourceConnection->getTableName('catalog_product_entity')],
                                [
                                    'entity_id' => 'product.entity_id',
                                    'website_id' => 'tier.website_id',
                                    'customer_group_id' => 'tier.customer_group_id'
                                ]
                            )
                            ->joinInner(
                                ['tier' => $this->resourceConnection->getTableName(
                                    'catalog_product_entity_tier_price'
                                )],
                                \sprintf('product.%1$s = tier.%1$s', $joinField) .
                                ' AND tier.qty=1 AND tier.all_groups = 0',
                                []
                            ),
                        $connection->select()
                            ->from(
                                ['product' => $this->resourceConnection->getTableName('catalog_product_entity')],
                                [
                                    'entity_id' => 'product.entity_id',
                                    'website_id' => 'rule.website_id',
                                    'customer_group_id' => 'sha1(rule.customer_group_id)'
                                ]
                            )
                            ->joinInner(
                                ['rule' => $this->resourceConnection->getTableName('catalogrule_product_price')],
                                'product.entity_id = rule.product_id' .
                                ' AND rule.rule_date = ' . $this->getWebsiteDate(),
                                []
                            )
                            ->joinLeft(
                                ['tier' => $this->resourceConnection->getTableName(
                                    'catalog_product_entity_tier_price'
                                )],
                                \sprintf('product.%1$s = tier.%1$s', $joinField) .
                                ' AND tier.qty=1 AND tier.all_groups = 0' .
                                ' AND tier.customer_group_id = rule.customer_group_id',
                                []
                            )
                            ->where('tier.value_id is NULL')
                    ], Select::SQL_UNION_ALL),
                ['qty' => new \Zend_Db_Expr('COUNT(DISTINCT entity_id, website_id, customer_group_id)')]
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

    /**
     * Gets website date
     *
     * @return \Zend_Db_Expr
     */
    private function getWebsiteDate(): \Zend_Db_Expr
    {
        $caseResults = [];
        foreach ($this->dateWebsiteProvider->getWebsitesDate() as $websiteId => $date) {
            $caseResults["rule.website_id = '$websiteId'"] = "'$date'";
        }
        return $this->resourceConnection->getConnection()->getCaseSql(
            '',
            $caseResults,
            'CURRENT_DATE'
        );
    }
}
