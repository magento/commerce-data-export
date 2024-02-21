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
 * Locates the current batch number.
 */
class BatchLocator
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var string
     */
    private string $sequenceTableName;

    /**
     * @var int
     */
    private int $autoIncrement = 1;

    /**
     * @var int
     */
    private int $autoIncrementOffset = 1;

    /**
     * @param ResourceConnection $resourceConnection
     * @param string $sequenceTableName
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        string $sequenceTableName
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->sequenceTableName = $sequenceTableName;
    }

    /**
     * Initializes the batch locator.
     *
     * @return void
     * @throws \Zend_Db_Exception
     */
    public function init(): void
    {
        $connection = $this->resourceConnection->getConnection();
        $mutexTable = $connection->newTable($this->sequenceTableName)
            ->addColumn(
                'i',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                10,
                [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => true,
                ],
                'Auto Increment ID'
            );
        $connection->dropTable($this->sequenceTableName);
        $connection->createTable($mutexTable);
        // phpcs:ignore Magento2.SQL.RawQuery.FoundRawSql
        $sql = 'SELECT @@auto_increment_increment as auto_increment, @@auto_increment_offset as auto_increment_offset';
        $result = $connection->query($sql)->fetch();
        $this->autoIncrement = (int)$result['auto_increment'];
        $this->autoIncrementOffset = (int)$result['auto_increment_offset'];
    }

    /**
     * Returns batch number.
     *
     * @return int
     */
    public function getNumber(): int
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->insert($this->sequenceTableName, []);
        $lastInsertId = (int)$connection->lastInsertId($this->sequenceTableName);
        $batchNumber = ($lastInsertId - $this->autoIncrementOffset) / $this->autoIncrement + 1;

        return (int)$batchNumber;
    }

    /**
     * Destroys the batch locator.
     *
     * @return void
     */
    public function destroy(): void
    {
        $this->resourceConnection->getConnection()->dropTable($this->sequenceTableName);
    }
}
