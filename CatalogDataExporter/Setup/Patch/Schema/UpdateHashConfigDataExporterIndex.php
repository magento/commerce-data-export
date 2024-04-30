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

namespace Magento\CatalogDataExporter\Setup\Patch\Schema;

use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Indexer\ConfigInterface;
use Magento\Indexer\Model\Indexer\StateFactory;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Update hash config data exporter index to keep previous indexer state
 */
class UpdateHashConfigDataExporterIndex implements SchemaPatchInterface
{
    private EncryptorInterface $encryptor;

    private ConfigInterface $config;

    private StateFactory $stateFactory;

    private Json $serializer;

    /**
     * @param SchemaSetupInterface $schemaSetup
     * @param StateFactory $stateFactory
     * @param ConfigInterface $config
     * @param EncryptorInterface $encryptor
     * @param Json $serializer
     */
    public function __construct(
        private readonly SchemaSetupInterface $schemaSetup,
        StateFactory $stateFactory,
        ConfigInterface $config,
        EncryptorInterface $encryptor,
        Json $serializer
    ) {
        $this->stateFactory = $stateFactory;
        $this->config = $config;
        $this->encryptor = $encryptor;
        $this->serializer = $serializer;
    }

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        $this->schemaSetup->startSetup();

        $tableIndexersList = [
            'catalog_data_exporter_products' => 'catalog_data_exporter_products'
        ];
        $connection = $this->schemaSetup->getConnection();
        foreach ($tableIndexersList as $legacyTableName => $indexerId) {
            $legacyTableName = $this->schemaSetup->getTable($legacyTableName);
            if ($connection->isTableExists($legacyTableName)
                && $connection->tableColumnExists($legacyTableName, 'status')) {
                $indexerConfig = $this->config->getIndexers()[$indexerId] ?? null;
                if ($indexerConfig) {
                    $newHash = $this->encryptor->hash(
                        $this->serializer->serialize($indexerConfig),
                        Encryptor::HASH_VERSION_MD5
                    );

                    $state = $this->stateFactory->create();
                    $state->loadByIndexer($indexerId);
                    $state->setHashConfig($newHash);
                    $state->save();
                }
            }
        }

        $this->schemaSetup->endSetup();

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [RenameOldChangeLogTables::class];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
