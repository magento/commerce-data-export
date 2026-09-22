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

use Magento\Framework\Indexer\IndexerInterface;
use AdobeCommerce\IndexerStatusManager\Model\Config;

/**
 * Skips reindexing for any indexer disabled via configuration.
 *
 * Wired once on IndexerInterface (every indexer wraps through it), the plugin reads the indexer id
 * from the subject, so no per-indexer configuration is needed -- disabling a new indexer is purely a
 * matter of setting its config flag. Covers the full-reindex, save-time row and list reindex paths.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class DisableIndexerReindex
{
    /**
     * @param Config $config
     */
    public function __construct(private readonly Config $config)
    {
    }

    /**
     * Skip reindexAll() when the indexer is disabled.
     *
     * @param IndexerInterface $subject
     * @param callable $proceed
     * @return void
     */
    public function aroundReindexAll(IndexerInterface $subject, callable $proceed)
    {
        if ($this->config->isDisabled((string)$subject->getId())) {
            return;
        }

        $proceed();
    }

    /**
     * Skip reindexRow() when the indexer is disabled.
     *
     * @param IndexerInterface $subject
     * @param callable $proceed
     * @param int $id
     * @return void
     */
    public function aroundReindexRow(IndexerInterface $subject, callable $proceed, $id)
    {
        if ($this->config->isDisabled((string)$subject->getId())) {
            return;
        }

        $proceed($id);
    }

    /**
     * Skip reindexList() when the indexer is disabled.
     *
     * @param IndexerInterface $subject
     * @param callable $proceed
     * @param int[] $ids
     * @return void
     */
    public function aroundReindexList(IndexerInterface $subject, callable $proceed, $ids)
    {
        if ($this->config->isDisabled((string)$subject->getId())) {
            return;
        }

        $proceed($ids);
    }
}
