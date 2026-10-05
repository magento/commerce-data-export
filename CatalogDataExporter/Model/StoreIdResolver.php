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

namespace Magento\CatalogDataExporter\Model;

use Magento\Framework\App\ResourceConnection;

/**
 * Resolves a store view code to its store_id.
 *
 * Shared helper for the data-export feed providers/queries, which receive the store view code on each
 * feed row and repeatedly need the numeric store_id. Reads the store table directly (no dependency on
 * the store manager / app scope) so it is usable from feed-building context, and caches per code for
 * the lifetime of the request.
 */
class StoreIdResolver
{
    /**
     * @var array<string, int> store view code => store_id
     */
    private array $storeIdCache = [];

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    /**
     * Returns the store_id for the given store view code. Result is cached per code.
     *
     * @param string $storeViewCode
     * @return int
     * @throws \InvalidArgumentException when the code cannot be resolved (except the "admin" store).
     */
    public function getStoreId(string $storeViewCode): int
    {
        if (!isset($this->storeIdCache[$storeViewCode])) {
            $connection = $this->resourceConnection->getConnection();
            $storeId = (int)$connection->fetchOne(
                $connection->select()
                    ->from(['store' => $this->resourceConnection->getTableName('store')], 'store_id')
                    ->where('store.code = ?', $storeViewCode)
            );
            if ($storeId === 0 && $storeViewCode !== 'admin') {
                throw new \InvalidArgumentException(
                    sprintf('Cannot resolve store_id for store view code "%s"', $storeViewCode)
                );
            }
            $this->storeIdCache[$storeViewCode] = $storeId;
        }

        return $this->storeIdCache[$storeViewCode];
    }
}
