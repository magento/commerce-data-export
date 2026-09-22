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

namespace AdobeCommerce\IndexerStatusManager\Model\Indexer;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Indexer\StateInterface;
use Magento\Framework\Mview\ViewInterface;
use Magento\Framework\Mview\ViewInterfaceFactory;
use Magento\Indexer\Model\Config\Data as IndexerConfigData;
use Magento\Indexer\Model\Indexer\StateFactory;
use AdobeCommerce\IndexerStatusManager\Model\Config;
use Psr\Log\LoggerInterface;

/**
 * Enables/disables an indexer by persisting its status flag and removing its mview triggers.
 *
 * Single home for the enable/disable business logic so every caller (the indexer:enable / indexer:disable
 * console commands, the setup Recurring that applies the shipped config defaults, etc.) goes through the
 * same guarded path. Only indexers on the Config allowlist may be toggled; anything else is rejected so a
 * critical indexer can never be switched off through this mechanism.
 *
 * Disabling also unsubscribes the indexer's materialized view (drops its DB triggers and changelog table)
 * so a disabled, Update-by-Schedule indexer does not keep filling a changelog that is never processed;
 * enabling re-subscribes it and invalidates the (now stale) index so it is rebuilt on the next reindex.
 *
 * Indexer state and mview are reached WITHOUT going through IndexerRegistry / Magento\Indexer\Model\Config:
 * the RemoveIndexersFromConfig plugin hides a disabled indexer from that config (and thus from
 * IndexerRegistry::get()), yet this service must still act on exactly those disabled indexers. The mview
 * View is loaded by view_id read from the raw (unfiltered) indexer configuration, and the indexer state is
 * loaded directly by its state row -- neither path is touched by the plugin.
 */
class StatusManager
{
    /**
     * @param WriterInterface $configWriter
     * @param Config $config
     * @param IndexerConfigData $indexerConfigData Raw, unfiltered indexer configuration (view_id source).
     * @param ViewInterfaceFactory $viewFactory
     * @param StateFactory $indexerStateFactory
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly WriterInterface $configWriter,
        private readonly Config $config,
        private readonly IndexerConfigData $indexerConfigData,
        private readonly ViewInterfaceFactory $viewFactory,
        private readonly StateFactory $indexerStateFactory,
        private readonly ResourceConnection $resourceConnection,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Disables the given indexer (skipped from reindex/mview/dependency chains and its triggers removed).
     *
     * @param string $indexerCode
     * @return void
     * @throws \InvalidArgumentException when the indexer is not on the allowlist.
     */
    public function disable(string $indexerCode): void
    {
        $this->setDisabled($indexerCode, true);
        $this->removeTriggers($indexerCode);
        $this->truncateIndexTables([$indexerCode]);
    }

    /**
     * Re-enables a previously disabled indexer, restores its mview triggers and invalidates it.
     *
     * The index was not maintained while the indexer was disabled, so its data is stale; invalidating
     * marks it for a full rebuild on the next reindex (cron or manual) now that reindex is no longer
     * suppressed.
     *
     * @param string $indexerCode
     * @return void
     * @throws \InvalidArgumentException when the indexer is not on the allowlist.
     */
    public function enable(string $indexerCode): void
    {
        $this->setDisabled($indexerCode, false);
        $this->restoreTriggers($indexerCode);
    }

    /**
     * Persists the disabled flag for an allowlisted indexer.
     *
     * @param string $indexerCode
     * @param bool $disabled
     * @return void
     * @throws \InvalidArgumentException when the indexer is not on the allowlist.
     */
    private function setDisabled(string $indexerCode, bool $disabled): void
    {
        if (!$this->config->isManageable($indexerCode)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Indexer "%s" is not managed by IndexerStatusManager. Managed indexers: %s',
                    $indexerCode,
                    implode(', ', $this->config->getManageableIndexers()) ?: '(none configured)'
                )
            );
        }

        $this->configWriter->save($this->config->getConfigPath($indexerCode), $disabled ? '1' : '0');
    }

    /**
     * Removes mview triggers for every manageable indexer that is currently disabled via configuration.
     *
     * Intended for the setup Recurring: it re-applies the config-driven disabled state after core's
     * indexer setup, so the shipped defaults (and any config:set values) drop their triggers on deploy
     * for both fresh and existing installs.
     *
     * @return void
     */
    public function removeTriggersForDisabledIndexers(): void
    {
        foreach ($this->config->getManageableIndexers() as $indexerCode) {
            if (!$this->config->isDisabled($indexerCode)) {
                continue;
            }
            try {
                $this->removeTriggers($indexerCode);
            } catch (\Throwable $e) {
                // One bad indexer must not abort setup; log and keep going.
                $this->logger->error(
                    sprintf(
                        'IndexerStatusManager: unable to remove mview triggers for disabled indexer "%s": %s',
                        $indexerCode,
                        $e->getMessage()
                    ),
                    ['exception' => $e]
                );
            }
        }
    }

    /**
     * Restores mview triggers for every manageable indexer that is no longer disabled via configuration
     * but whose view is still unsubscribed (e.g. the flag was removed straight from env.php, not via our
     * enable() CLI command).
     *
     * Intended for the setup Recurring, run right after removeTriggersForDisabledIndexers(): without this,
     * such an indexer would stay unsubscribed forever, since core's own recurring skips anything already
     * in disabled mode. Calls restoreTriggers() directly (not enable()), so no config flag gets rewritten
     * -- it is already known to be false, that is exactly why the indexer is in this loop.
     *
     * @return void
     */
    public function restoreTriggersForEnabledIndexers(): void
    {
        foreach ($this->config->getManageableIndexers() as $indexerCode) {
            if ($this->config->isDisabled($indexerCode)) {
                continue;
            }
            try {
                $this->restoreTriggers($indexerCode);
            } catch (\Throwable $e) {
                // One bad indexer must not abort setup; log and keep going.
                $this->logger->error(
                    sprintf(
                        'IndexerStatusManager: unable to restore mview triggers for indexer "%s": %s',
                        $indexerCode,
                        $e->getMessage()
                    ),
                    ['exception' => $e]
                );
            }
        }
    }

    /**
     * Truncates the configured index tables of disabled indexers to free space and expose downstream issues.
     *
     * Once an indexer stops being maintained its index table only holds stale data and consumes space; this
     * empties it so the storefront/feed path is exercised without a populated local index. A table is
     * truncated only when EVERY manageable indexer that writes it is disabled, so a table shared by several
     * indexers (e.g. catalog_category_product_index, written by both catalog_category_product and
     * catalog_product_category) is never emptied while one of them is still enabled. Consumer modules declare
     * the tables per indexer via Config::$indexTables; the generic engine ships none, so this is a no-op
     * until a consumer opts in. There is no OpenSearch-backed index here (e.g. catalogsearch_fulltext) -- its
     * store is not a DB table and is out of scope. Missing tables and truncation errors are logged and
     * skipped, never aborting the caller.
     *
     * @param string[] $justDisabledIndexers Codes disabled in this same request whose flag may not yet be
     *        visible to the scope config; treated as disabled without re-reading configuration.
     * @return void
     */
    public function truncateIndexTables(array $justDisabledIndexers = []): void
    {
        $justDisabled = array_fill_keys($justDisabledIndexers, true);
        $connection = $this->resourceConnection->getConnection();

        foreach ($this->collectTruncatableTables($justDisabled) as $logicalTable) {
            try {
                $table = $this->resourceConnection->getTableName($logicalTable);
                if ($connection->isTableExists($table)) {
                    $connection->truncateTable($table);
                }
            } catch (\Throwable $e) {
                $this->logger->error(
                    sprintf(
                        'IndexerStatusManager: unable to truncate index table "%s": %s',
                        $logicalTable,
                        $e->getMessage()
                    ),
                    ['exception' => $e]
                );
            }
        }
    }

    /**
     * Returns the logical index tables safe to truncate: those whose every writing indexer is disabled.
     *
     * Inverts the per-indexer table map (indexerCode => tables) into table => owning indexer codes, then
     * keeps a table only when all its owners are disabled (via config, or listed in $justDisabled).
     *
     * @param array<string,bool> $justDisabled
     * @return string[]
     */
    private function collectTruncatableTables(array $justDisabled): array
    {
        $tableOwners = [];
        foreach ($this->config->getManageableIndexers() as $indexerCode) {
            foreach ($this->config->getIndexTables($indexerCode) as $logicalTable) {
                $tableOwners[$logicalTable][$indexerCode] = $indexerCode;
            }
        }

        $truncatable = [];
        foreach ($tableOwners as $logicalTable => $owners) {
            foreach ($owners as $indexerCode) {
                if (!isset($justDisabled[$indexerCode]) && !$this->config->isDisabled($indexerCode)) {
                    // A still-enabled indexer also writes this table; leave it alone.
                    continue 2;
                }
            }
            $truncatable[] = (string)$logicalTable;
        }

        return $truncatable;
    }

    /**
     * Unsubscribes the indexer's materialized view (drops its DB triggers and changelog) and invalidates it.
     *
     * Mirrors Magento\Indexer\Model\Indexer::setScheduled(false), which also invalidates: once change
     * capture stops the index goes stale, so it must be marked invalid to rebuild whenever it is re-enabled
     * -- including when the disabled flag is simply removed (uninstall / config change) rather than via
     * enable(). No-op when the indexer has no view. View::unsubscribe() is idempotent (it checks the current
     * mode), so this is safe to call repeatedly.
     *
     * @param string $indexerCode
     * @return void
     */
    private function removeTriggers(string $indexerCode): void
    {
        $view = $this->loadView($indexerCode);
        if ($view === null) {
            return;
        }
        $view->unsubscribe();
        $this->invalidateIndexer($indexerCode);
    }

    /**
     * Re-subscribes the indexer's materialized view, recreating its DB triggers and changelog table.
     *
     * Only invalidates when actually flipping from unsubscribed to subscribed -- the index was not
     * maintained while unsubscribed, so its data is stale and must be rebuilt. Skips invalidation (and
     * the subscribe call) when already subscribed, so calling this on an indexer that was never disabled
     * is a safe no-op. No-op entirely when the indexer has no view.
     *
     * @param string $indexerCode
     * @return void
     */
    private function restoreTriggers(string $indexerCode): void
    {
        $view = $this->loadView($indexerCode);
        if ($view !== null && !$view->isEnabled()) {
            $view->subscribe();
            $this->invalidateIndexer($indexerCode);
        }
    }

    /**
     * Loads the indexer's mview View by its view_id, or null when the indexer has no view.
     *
     * Reads view_id from the raw indexer configuration (not IndexerRegistry / Magento\Indexer\Model\Config),
     * so it works even for an indexer the RemoveIndexersFromConfig plugin hides while disabled.
     *
     * @param string $indexerCode
     * @return ViewInterface|null
     */
    private function loadView(string $indexerCode): ?ViewInterface
    {
        $indexer = $this->indexerConfigData->get($indexerCode);
        $viewId = is_array($indexer) ? (string)($indexer['view_id'] ?? '') : '';
        if ($viewId === '') {
            return null;
        }

        $view = $this->viewFactory->create();
        $view->load($viewId);

        return $view;
    }

    /**
     * Marks the indexer's state invalid so it is rebuilt on the next reindex.
     *
     * Operates on the state row directly (loaded by indexer id) rather than via IndexerRegistry, which
     * would not resolve an indexer the RemoveIndexersFromConfig plugin still hides at the moment the flag
     * change has not yet propagated to the in-memory scope config.
     *
     * @param string $indexerCode
     * @return void
     */
    private function invalidateIndexer(string $indexerCode): void
    {
        $state = $this->indexerStateFactory->create();
        $state->loadByIndexer($indexerCode);
        $state->setIndexerId($indexerCode);
        $state->setStatus(StateInterface::STATUS_INVALID);
        $state->save();
    }
}
