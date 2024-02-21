<?php
/*************************************************************************
 *
 * Copyright 2023 Adobe
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
 * ***********************************************************************
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Batch;

use Magento\Framework\App\ResourceConnection;

/**
 * Batch table management.
 */
class BatchTable
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var string
     */
    private string $batchNumberField = 'batch_number';

    /**
     * @var string
     */
    private string $batchTableName;

    /**
     * @var string
     */
    private string $sourceTableName;

    /**
     * @var array
     */
    private array $sourceTableKeyColumns;

    /**
     * @param ResourceConnection $resourceConnection
     * @param string $batchTableName
     * @param string $sourceTableName
     * @param array $sourceTableKeyColumns
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        string $batchTableName,
        string $sourceTableName,
        array $sourceTableKeyColumns
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->batchTableName = $batchTableName;
        $this->sourceTableName = $sourceTableName;
        $this->sourceTableKeyColumns = $sourceTableKeyColumns;
    }

    /**
     * Creates batch table.
     *
     * @param string $insertDataQuery
     * @throws \Zend_Db_Exception
     */
    public function create(string $insertDataQuery): void
    {
        $connection = $this->resourceConnection->getConnection();
        $batchTable = $connection->newTable()
            ->setName($this->batchTableName)
            ->addColumn(
                $this->batchNumberField,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                10,
                ['nullable' => false],
                'Batch Number'
            );

        $keyColumns = $this->getColumnDefinitions($this->sourceTableName, $this->sourceTableKeyColumns);
        array_walk(
            $keyColumns,
            function ($definition) use ($connection, $batchTable) {
                $columnInfo = $connection->getColumnCreateByDescribe($definition);
                unset($columnInfo['options']['primary']);
                unset($columnInfo['options']['identity']);
                $batchTable->addColumn(
                    $columnInfo['name'],
                    $columnInfo['type'],
                    $columnInfo['length'],
                    $columnInfo['options'],
                    $columnInfo['comment']
                );
            }
        );
        $batchTable->addIndex(
            $connection->getIndexName(
                $this->batchTableName,
                array_merge([$this->batchNumberField], array_keys($keyColumns))
            ),
            array_merge([$this->batchNumberField], array_keys($keyColumns)),
            ['type' => 'primary']
        );

        $connection->dropTable($this->batchTableName);
        $connection->createTable($batchTable);
        $connection->query($insertDataQuery);
        $connection->query(sprintf("ANALYZE TABLE %s", $this->batchTableName));
    }

    /**
     * Drops batch table.
     *
     * @return void
     */
    public function drop(): void
    {
        $this->resourceConnection->getConnection()->dropTable($this->batchTableName);
    }

    /**
     * Returns the count of batches.
     *
     * @return int
     */
    public function getBatchCount(): int
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                [$this->batchTableName],
                $this->batchNumberField
            )->order($this->batchNumberField . ' DESC')
            ->limit(1);

        return (int)$connection->fetchOne($select);
    }

    /**
     * Returns the name of the batch number field.
     *
     * @return string
     */
    public function getBatchNumberField(): string
    {
        return $this->batchNumberField;
    }

    /**
     * Returns the name of the batch table.
     *
     * @return string
     */
    public function getBatchTableName(): string
    {
        return $this->batchTableName;
    }

    /**
     * Returns the column definitions for the given table and column names.
     *
     * @param string $tableName
     * @param array $columnNames
     * @return array
     */
    private function getColumnDefinitions(string $tableName, array $columnNames): array
    {
        $result = array_filter(
            $this->resourceConnection->getConnection()->describeTable($tableName),
            function ($column) use ($columnNames) {
                return in_array($column['COLUMN_NAME'], $columnNames);
            }
        );

        return $result;
    }
}
