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

use Magento\Indexer\Model\Config as IndexerConfig;
use AdobeCommerce\IndexerStatusManager\Model\Config;

/**
 * Removes config-disabled indexers from the indexer configuration.
 *
 * Any indexer whose code resolves to disabled (Config::isDisabled()) is dropped from the indexer
 * list, so it is neither listed nor executed by bin/magento indexer:reindex / indexer:info.
 */
class RemoveIndexersFromConfig
{
    /**
     * @param Config $config
     */
    public function __construct(private readonly Config $config)
    {
    }

    /**
     * Drop every indexer whose code is disabled via configuration.
     *
     * @param IndexerConfig $subject
     * @param array[] $result
     * @return array[]
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetIndexers(IndexerConfig $subject, array $result): array
    {
        foreach ($result as $indexerId => $indexerData) {
            if ($this->config->isDisabled((string)$indexerId)) {
                unset($result[$indexerId]);
                continue;
            }
            $result[$indexerId] = $this->removeDisabledDependencies($indexerData);
        }

        return $result;
    }

    /**
     * Hide a disabled indexer from single-ID lookups too (bypasses getIndexers() otherwise).
     *
     * @param IndexerConfig $subject
     * @param array $result
     * @param string $indexerId
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetIndexer(IndexerConfig $subject, array $result, $indexerId): array
    {
        if ($this->config->isDisabled((string)$indexerId)) {
            return [];
        }

        return $this->removeDisabledDependencies($result);
    }

    /**
     * Strips disabled indexer IDs out of a 'dependencies' list so callers reading the raw config
     * (e.g. Processor::hasPendingDependencies()) never try to load a disabled indexer by ID.
     *
     * @param array $indexerData
     * @return array
     */
    private function removeDisabledDependencies(array $indexerData): array
    {
        if (!empty($indexerData['dependencies'])) {
            $indexerData['dependencies'] = array_values(array_filter(
                $indexerData['dependencies'],
                fn ($depId): bool => !$this->config->isDisabled((string)$depId)
            ));
        }

        return $indexerData;
    }
}
