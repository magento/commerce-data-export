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

namespace AdobeCommerce\IndexerStatusManager\Plugin;

use Magento\Framework\Indexer\ConfigInterface as IndexerConfig;
use Magento\Framework\Mview\ViewInterface;
use AdobeCommerce\IndexerStatusManager\Model\Config;

/**
 * Skips materialized-view (changelog) processing for any indexer disabled via configuration.
 *
 * Covers the scheduled "update by schedule" path, where the view runs the indexer action directly
 * instead of through the indexer model. The mview id is NOT guaranteed to equal the indexer id
 * (indexer.xml declares an explicit view_id that may differ), so the indexer id is resolved from the
 * view id via the indexer configuration (view_id => indexer_id) before consulting the config flag.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class DisableMviewUpdate
{
    /**
     * @var array<string, string>|null Lazily built map of view_id => indexer_id.
     */
    private ?array $indexerIdByViewId = null;

    /**
     * @param Config $config
     * @param IndexerConfig $indexerConfig
     */
    public function __construct(
        private readonly Config $config,
        private readonly IndexerConfig $indexerConfig
    ) {
    }

    /**
     * Skip the mview update() when the corresponding indexer is disabled.
     *
     * @param ViewInterface $subject
     * @param callable $proceed
     * @return void
     */
    public function aroundUpdate(ViewInterface $subject, callable $proceed)
    {
        $indexerId = $this->resolveIndexerId((string)$subject->getId());
        if ($indexerId !== null && $this->config->isDisabled($indexerId)) {
            return;
        }

        $proceed();
    }

    /**
     * Resolves the indexer id that owns the given mview id, or null when no indexer declares it.
     *
     * The indexer.xml maps each indexer to a view via its view_id; this inverts that mapping so a view
     * can be traced back to its indexer even when the two ids differ.
     *
     * @param string $viewId
     * @return string|null
     */
    private function resolveIndexerId(string $viewId): ?string
    {
        if ($this->indexerIdByViewId === null) {
            $this->indexerIdByViewId = [];
            foreach ($this->indexerConfig->getIndexers() as $indexerId => $indexer) {
                $mappedViewId = $indexer['view_id'] ?? '';
                if ($mappedViewId !== '') {
                    $this->indexerIdByViewId[$mappedViewId] = (string)$indexerId;
                }
            }
        }

        return $this->indexerIdByViewId[$viewId] ?? null;
    }
}
