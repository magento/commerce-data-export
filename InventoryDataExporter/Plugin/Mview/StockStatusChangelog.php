<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryDataExporter\Plugin\Mview;

use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Mview\View\Changelog;
use Magento\Framework\Mview\View\ChangelogTableNotExistsException;
use Magento\Framework\Phrase;

/**
 * Override default behavior for Mview:
 * - allow to collect String instead Integer for "inventory_data_exporter_stock_status" changelog table
 */
class StockStatusChangelog
{
    private const SKU_FIELD_SIZE = 64;
    private const STOCK_STATUS_CHANGELOG_NAME = 'inventory_data_exporter_stock_status_' . Changelog::NAME_SUFFIX;

    private ResourceConnection $resource;
    private AdapterInterface $connection;
    private CommerceDataExportLoggerInterface $logger;

    /**
     * Constructor for class StockStatusChangelog
     *
     * @param ResourceConnection $resource
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resource,
        CommerceDataExportLoggerInterface $logger
    ) {
        $this->connection = $resource->getConnection();
        $this->resource = $resource;
        $this->logger = $logger;
    }

    /**
     * Around plugin for create method
     *
     * @param Changelog $subject
     * @param callable $proceed
     * @return void
     * @throws \Zend_Db_Exception
     */
    public function aroundCreate(
        Changelog $subject,
        callable  $proceed
    ): void {
        try {
            if ($this->isStockStatusChangelog($subject)) {
                $this->createChangelogTable($subject);
            } else {
                $proceed();
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                'Data Exporter exception has occurred: ' . $e->getMessage(),
                ['exception' => $e]
            );
            $proceed();
        }
    }

    /**
     * Create changelog table
     *
     * @param Changelog $subject
     * @throws \Zend_Db_Exception
     */
    private function createChangelogTable(Changelog $subject): void
    {
        $changelogTableName = $this->resource->getTableName($subject->getName());
        if (!$this->connection->isTableExists($changelogTableName)) {
            $table = $this->connection->newTable(
                $changelogTableName
            )->addColumn(
                'version_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Version ID'
            )->addColumn(
                $subject->getColumnName(),
                Table::TYPE_TEXT,
                self::SKU_FIELD_SIZE,
                ['nullable' => false],
                'Entity SKU'
            );
            $this->connection->createTable($table);
        }
    }

    /**
     * Is stock status has changelog
     *
     * @param Changelog $changelog
     * @return bool
     */
    private function isStockStatusChangelog(Changelog $changelog): bool
    {
        return $changelog->getName() === self::STOCK_STATUS_CHANGELOG_NAME;
    }

    /**
     * Override original method
     *
     * Return list of SKUs instead of
     * retrieve entity ids by range [$fromVersionId..$toVersionId]
     *
     * @param Changelog $subject
     * @param callable $proceed
     * @param int $fromVersionId
     * @param int $toVersionId
     * @return string[]
     * @throws ChangelogTableNotExistsException
     */
    public function aroundGetList(
        Changelog $subject,
        callable $proceed,
        $fromVersionId,
        $toVersionId
    ) {
        try {
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

        } catch (\Throwable $e) {
            $this->logger->error(
                'Data Exporter exception has occurred: ' . $e->getMessage(),
                ['exception' => $e]
            );
            return $proceed($fromVersionId, $toVersionId);
        }
    }
}
