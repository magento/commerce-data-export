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

namespace AdobeCommerce\IndexerStatusManager\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Resolves whether a given indexer is disabled via configuration.
 */
class Config
{
    /**
     * Config path prefix; the indexer code is appended to it to form the full flag path
     * (e.g. ac_disabled_indexers/catalog_category_product).
     *
     * Deliberately its own top-level section rather than nested under commerce_data_export,
     * which is owned/read by the DataExporter module -- this module has no dependency on it.
     */
    private const XML_PATH_PREFIX = 'ac_disabled_indexers/';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param string[] $manageableIndexers Codes of indexers allowed to be disabled through this module.
     * @param array<string,string[]> $indexTables Logical index table names to truncate per indexer code
     *        when it is disabled (indexerCode => [table, ...]). Contributed by consumer modules; empty here.
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly array $manageableIndexers = [],
        private readonly array $indexTables = []
    ) {
    }

    /**
     * Returns whether the indexer identified by the given code is disabled via configuration.
     *
     * The disabled flag is honored only for indexers on the allowlist. A flag set for any other
     * indexer -- whether via the CLI, config:set, or a hand-edited env.php -- is ignored, so a
     * critical indexer (price, stock, ...) can never be silently switched off through this mechanism.
     *
     * @param string $indexerCode
     * @return bool
     */
    public function isDisabled(string $indexerCode): bool
    {
        return $this->isManageable($indexerCode)
            && $this->scopeConfig->isSetFlag($this->getConfigPath($indexerCode));
    }

    /**
     * Returns whether the given indexer is allowed to be disabled through this module.
     *
     * @param string $indexerCode
     * @return bool
     */
    public function isManageable(string $indexerCode): bool
    {
        return in_array($indexerCode, $this->manageableIndexers, true);
    }

    /**
     * Returns the codes of all indexers that are allowed to be disabled through this module.
     *
     * @return string[]
     */
    public function getManageableIndexers(): array
    {
        return $this->manageableIndexers;
    }

    /**
     * Returns the logical index table names to truncate for the given indexer when it is disabled.
     *
     * Empty when the consumer declared none. A table shared by several indexers is listed under each of
     * them, so the caller can require all of them to be disabled before truncating it.
     *
     * @param string $indexerCode
     * @return string[]
     */
    public function getIndexTables(string $indexerCode): array
    {
        return $this->indexTables[$indexerCode] ?? [];
    }

    /**
     * Returns the full config path that stores the disabled flag for the given indexer code.
     *
     * @param string $indexerCode
     * @return string
     */
    public function getConfigPath(string $indexerCode): string
    {
        return self::XML_PATH_PREFIX . $indexerCode;
    }
}
