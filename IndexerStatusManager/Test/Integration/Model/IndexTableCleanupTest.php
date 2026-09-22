<?php
/**
 * Copyright 2026 Adobe
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

namespace AdobeCommerce\IndexerStatusManager\Test\Integration\Model;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use AdobeCommerce\IndexerStatusManager\Model\Config;
use AdobeCommerce\IndexerStatusManager\Model\Indexer\StatusManager;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration coverage for the index-table cleanup on disable.
 *
 * Uses throwaway tables mapped to fake indexer codes so the behavior is exercised without touching a real
 * indexer's data: a table is emptied only when every indexer that writes it is disabled, so a table shared
 * with a still-enabled indexer is left intact.
 *
 * DbIsolation is off: create/drop/truncate are DDL, which the transaction adapter rejects; tearDown drops
 * the throwaway tables and clears any disabled flag disable() persisted.
 */
#[
    AppIsolation(true),
    DbIsolation(false)
]
class IndexTableCleanupTest extends TestCase
{
    private const SHARED_TABLE = 'test_ism_index_cleanup_shared';
    private const SOLO_TABLE = 'test_ism_index_cleanup_solo';
    private const INDEXER_A = 'ism_test_indexer_a';
    private const INDEXER_B = 'ism_test_indexer_b';
    private const INDEXER_SOLO = 'ism_test_indexer_solo';

    /**
     * @var StatusManager
     */
    private StatusManager $service;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var WriterInterface
     */
    private WriterInterface $configWriter;

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resource;

    /**
     * @var AdapterInterface
     */
    private AdapterInterface $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $objectManager = Bootstrap::getObjectManager();
        // Fake manageable indexers: A and B share one table (the shared-table case); SOLO owns its own.
        $objectManager->configure([
            Config::class => [
                'arguments' => [
                    'manageableIndexers' => [self::INDEXER_A, self::INDEXER_B, self::INDEXER_SOLO],
                    'indexTables' => [
                        self::INDEXER_A => [self::SHARED_TABLE],
                        self::INDEXER_B => [self::SHARED_TABLE],
                        self::INDEXER_SOLO => [self::SOLO_TABLE],
                    ],
                ],
            ],
        ]);
        $this->service = $objectManager->get(StatusManager::class);
        $this->config = $objectManager->get(Config::class);
        $this->configWriter = $objectManager->get(WriterInterface::class);
        $this->resource = $objectManager->get(ResourceConnection::class);
        $this->connection = $this->resource->getConnection();

        $this->createTableWithRow(self::SHARED_TABLE);
        $this->createTableWithRow(self::SOLO_TABLE);
    }

    protected function tearDown(): void
    {
        foreach ([self::SHARED_TABLE, self::SOLO_TABLE] as $logicalTable) {
            $table = $this->resource->getTableName($logicalTable);
            if ($this->connection->isTableExists($table)) {
                $this->connection->dropTable($table);
            }
        }
        // disable() persists a config flag (DbIsolation is off); clear the ones tests may have written.
        foreach ([self::INDEXER_A, self::INDEXER_B, self::INDEXER_SOLO] as $indexerCode) {
            $this->configWriter->delete($this->config->getConfigPath($indexerCode));
        }
        parent::tearDown();
    }

    /**
     * Disabling an indexer truncates the index table it owns.
     */
    public function testDisableTruncatesOwnIndexTable(): void
    {
        $this->assertSame(1, $this->rowCount(self::SOLO_TABLE), 'Precondition: the table starts with a row.');

        $this->service->disable(self::INDEXER_SOLO);

        $this->assertSame(
            0,
            $this->rowCount(self::SOLO_TABLE),
            'Disabling an indexer must truncate the index table it owns.'
        );
    }

    /**
     * The table is emptied when every indexer that writes it is disabled.
     */
    public function testTruncatesTableWhenAllOwnersDisabled(): void
    {
        $this->assertSame(1, $this->rowCount(self::SHARED_TABLE), 'Precondition: the table starts with a row.');

        $this->service->truncateIndexTables([self::INDEXER_A, self::INDEXER_B]);

        $this->assertSame(
            0,
            $this->rowCount(self::SHARED_TABLE),
            'A table whose every writing indexer is disabled must be truncated.'
        );
    }

    /**
     * The table is left intact while any indexer that writes it is still enabled.
     */
    public function testKeepsSharedTableWhileAnyOwnerEnabled(): void
    {
        $this->assertSame(1, $this->rowCount(self::SHARED_TABLE), 'Precondition: the table starts with a row.');

        // Only indexer A is disabled; B is still enabled (no flag), so the shared table must be kept.
        $this->service->truncateIndexTables([self::INDEXER_A]);

        $this->assertSame(
            1,
            $this->rowCount(self::SHARED_TABLE),
            'A table shared with a still-enabled indexer must not be truncated.'
        );
    }

    /**
     * Creates a throwaway table with a single row.
     *
     * @param string $logicalTable
     * @return void
     */
    private function createTableWithRow(string $logicalTable): void
    {
        $table = $this->resource->getTableName($logicalTable);
        if ($this->connection->isTableExists($table)) {
            $this->connection->dropTable($table);
        }
        $this->connection->createTable(
            $this->connection->newTable($table)
                ->addColumn('id', Table::TYPE_INTEGER, null, ['nullable' => false, 'primary' => true])
        );
        $this->connection->insert($table, ['id' => 1]);
    }

    /**
     * Counts the rows currently in the given throwaway table.
     *
     * @param string $logicalTable
     * @return int
     */
    private function rowCount(string $logicalTable): int
    {
        $table = $this->resource->getTableName($logicalTable);

        return (int)$this->connection->fetchOne($this->connection->select()->from($table, 'COUNT(*)'));
    }
}
