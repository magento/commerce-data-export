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

namespace AdobeCommerce\IndexerStatusManager\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use AdobeCommerce\IndexerStatusManager\Model\Indexer\StatusManager;
use Psr\Log\LoggerInterface;

/**
 * Syncs mview triggers with the configured disabled state on every setup:upgrade, in both directions.
 *
 * This module sequences Magento_Indexer, so this recurring script runs AFTER core's
 * Magento\Indexer\Setup\Recurring, which (re)subscribes scheduled indexers and creates triggers. Any
 * triggers core created for an indexer that configuration marks disabled are dropped again here, so the
 * shipped config defaults take effect on both fresh and existing installs. Conversely, an indexer no
 * longer marked disabled (flag removed/flipped outside our enable() CLI command, e.g. via env.php or
 * config:set) gets its triggers restored here -- otherwise it would stay unsubscribed forever, since
 * core's own recurring skips anything already in disabled mode.
 *
 * Runs the removal pass first, then the restore pass -- the two are mutually exclusive per indexer
 * (each is disabled or not), so order between them doesn't matter, but keeping removal first mirrors the
 * historical behavior this recurring started with.
 */
class Recurring implements InstallSchemaInterface
{
    /**
     * @param StatusManager $statusManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly StatusManager $statusManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        try {
            $this->statusManager->removeTriggersForDisabledIndexers();
            $this->statusManager->restoreTriggersForEnabledIndexers();
            $this->statusManager->truncateIndexTables();
        } catch (\Throwable $e) {
            // Never let trigger cleanup abort setup.
            $this->logger->error(
                sprintf('IndexerStatusManager recurring setup failed: %s', $e->getMessage()),
                ['exception' => $e]
            );
        }
    }
}
