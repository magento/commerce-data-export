<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryDataExporter\Plugin\Mview;

use Magento\Framework\DB\Adapter\ConnectionException;
use Magento\Framework\Mview\View\Changelog;
use Magento\Framework\Mview\View\ChangelogTableNotExistsException;
use Magento\Framework\Phrase;

/**
 * Override default behavior for Mview:
 * - allow to collect String instead Integer for "inventory_data_exporter_stock_status" changelog table
 */
class StockStatusChangelog
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;

    private const SKU_FIELD_SIZE = 64;
    private const STOCK_STATUS_CHANGELOG_NAME = 'inventory_data_exporter_stock_status_' . Changelog::NAME_SUFFIX;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @throws ConnectionException
     */
    public function __construct(\Magento\Framework\App\ResourceConnection $resource)
    {
        $this->connection = $resource->getConnection();
        $this->resource = $resource;
    }

    public function aroundCreate(
        \Magento\Framework\Mview\View\Changelog $subject,
        callable $proceed
    ): void {
        if ($this->isStockStatusChangelog($subject)) {
            $this->createChangelogTable($subject);
        } else {
            $proceed();
        }
    }

    /**
     * @param Changelog $subject
     */
    private function createChangelogTable(Changelog $subject): void
    {
        $changelogTableName = $this->resource->getTableName($subject->getName());
        if (!$this->connection->isTableExists($changelogTableName)) {
            $table = $this->connection->newTable(
                $changelogTableName
            )->addColumn(
                'version_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Version ID'
            )->addColumn(
                $subject->getColumnName(),
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                self::SKU_FIELD_SIZE,
                ['nullable' => false],
                'Entity SKU'
            );
            $this->connection->createTable($table);
        }
    }

    /**
     * @param Changelog $changelog
     * @return bool
     */
    private function isStockStatusChangelog(Changelog $changelog): bool
    {
        return $changelog->getName() === self::STOCK_STATUS_CHANGELOG_NAME;
    }

    /**
     * Override original method: return list of SKUs instead of
     * Retrieve entity ids by range [$fromVersionId..$toVersionId]
     *
     * @param Changelog $subject
     * @param callable $proceed
     * @param int $fromVersionId
     * @param int $toVersionId
     * @return string[]
     * @throws ChangelogTableNotExistsException
     */
    public function aroundGetList(
        \Magento\Framework\Mview\View\Changelog $subject,
        callable $proceed,
        $fromVersionId,
        $toVersionId
    ) {
        if (!$this->isStockStatusChangelog($subject)) {
            return $proceed($fromVersionId, $toVersionId);
        }
        $changelogTableName = $this->resource->getTableName($subject->getName());
        if (!$this->connection->isTableExists($changelogTableName)) {
            throw new ChangelogTableNotExistsException(new Phrase("Table %1 does not exist", [$changelogTableName]));
        }

        $select = $this->connection->select()->distinct(
            true
        )->from(
            $changelogTableName,
            [$subject->getColumnName()]
        )->where(
            'version_id > ?',
            (int)$fromVersionId
        )->where(
            'version_id <= ?',
            (int)$toVersionId
        );

        return $this->connection->fetchCol($select);
    }
}
