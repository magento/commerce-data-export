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

namespace Magento\DataExporter\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class DropLegacyTablesAbstract
{
    /**
     * @var string[]
     */
    private array $suffixesToRemove = [
        '_cl_index_batches',
        '_cl_index_sequence',
        '_index_batches',
        '_index_sequence',
        '_sync_batches',
        '_sync_sequence',
    ];

    /**
     * @param SchemaSetupInterface $schemaSetup
     */
    public function __construct(
        private readonly SchemaSetupInterface $schemaSetup
    ) {}

    /**
     * Drop tables
     *
     * @param string[] $tableList
     * @return $this
     */
    public function dropTables(array $tableList): self
    {
        $this->schemaSetup->startSetup();

        $connection = $this->schemaSetup->getConnection();
        foreach ($tableList as $legacyTableName) {
            $tableName = $this->schemaSetup->getTable($legacyTableName);
            $this->dropTable($connection, $tableName);
            foreach ($this->suffixesToRemove as $suffix) {
                $this->dropTable($connection, $tableName . $suffix);
            }
        }

        $this->schemaSetup->endSetup();

        return $this;
    }

    /**
     * Drop table if exists
     *
     * @param AdapterInterface $connection
     * @param string $tableName
     * @return void
     */
    private function dropTable(AdapterInterface $connection, string $tableName): void
    {
        if ($connection->isTableExists($tableName)) {
            $this->schemaSetup->getConnection()->dropTable($tableName);
        }
    }
}
