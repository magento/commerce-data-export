<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Category;

use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Provides active/menu status for ancestor categories, with per-request memoization.
 */
class AncestorStatusProvider
{
    /** @var array<string, array<int, array{isActive: int, includeInMenu: int}|null>> */
    private array $cache = [];

    /** @var array{isActive: int, includeInMenu: int}|null */
    private ?array $attributeIds = null;

    /** @var array<string, int> */
    private array $storeIdCache = [];

    /**
     * @param ResourceConnection $resourceConnection
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Returns ancestor category status for "isActive" and "includeInMenu" attributes independently.
     *
     * @param int $ancestorId
     * @param string $storeViewCode
     * @return array{isActive: bool, includeInMenu: bool}
     */
    public function getAncestorStatus(int $ancestorId, string $storeViewCode): array
    {
        if (!array_key_exists($ancestorId, $this->cache[$storeViewCode] ?? [])) {
            $this->loadAncestorData($ancestorId, $storeViewCode);
        }

        $data = $this->cache[$storeViewCode][$ancestorId] ?? null;

        return [
            'isActive'      => $data === null || (bool)$data['isActive'],
            'includeInMenu' => $data === null || (bool)$data['includeInMenu'],
        ];
    }

    /**
     * Loads and caches EAV data for the given category in the given store.
     *
     * @param int $categoryId
     * @param string $storeViewCode
     * @return void
     */
    private function loadAncestorData(int $categoryId, string $storeViewCode): void
    {
        $connection = $this->resourceConnection->getConnection();
        $storeId = $this->getStoreId($storeViewCode);
        $attrIds = $this->getAttributeIds();

        $categoryTable = $this->resourceConnection->getTableName('catalog_category_entity');
        $linkField = $connection->getAutoIncrementField($categoryTable);

        $select = $connection->select()
            ->from(
                ['e' => $categoryTable],
                ['entity_id']
            )
            ->join(
                ['eav' => $this->resourceConnection->getTableName('catalog_category_entity_int')],
                "e.{$linkField} = eav.{$linkField}"
                . $connection->quoteInto(
                    ' AND eav.attribute_id IN (?)',
                    [$attrIds['isActive'], $attrIds['includeInMenu']]
                )
                . $connection->quoteInto(' AND eav.store_id IN (?)', [Store::DEFAULT_STORE_ID, $storeId]),
                ['store_id', 'value', 'attribute_id']
            )
            ->where('e.entity_id = ?', $categoryId);

        $rows = $connection->fetchAll($select);

        // Mark all requested IDs as loaded (null = no EAV data = treat as active)
        $this->cache[$storeViewCode][$categoryId] ??= null;

        $rawData = [];
        foreach ($rows as $row) {
            $rawData[(int)$row['entity_id']][(int)$row['attribute_id']][(int)$row['store_id']] = (int)$row['value'];
        }

        foreach ($rawData as $categoryId => $attributeData) {
            $this->cache[$storeViewCode][$categoryId] = [
                'isActive'      => $this->resolveValue($attributeData[$attrIds['isActive']] ?? []),
                'includeInMenu' => $this->resolveValue($attributeData[$attrIds['includeInMenu']] ?? []),
            ];
        }
    }

    /**
     * Returns the effective value applying store-specific-over-default fallback.
     *
     * Store-specific row (store_id != 0) wins
     *
     * @param array $storeValues
     * @return int
     */
    private function resolveValue(array $storeValues): int
    {
        foreach ($storeValues as $storeId => $value) {
            if ($storeId !== Store::DEFAULT_STORE_ID) {
                return $value;
            }
        }
        return $storeValues[Store::DEFAULT_STORE_ID] ?? 0;
    }

    /**
     * Returns attribute IDs for is_active and include_in_menu.
     *
     * @return array{isActive: int, includeInMenu: int}
     */
    private function getAttributeIds(): array
    {
        if ($this->attributeIds !== null) {
            return $this->attributeIds;
        }

        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                ['a' => $this->resourceConnection->getTableName('eav_attribute')],
                ['attribute_code', 'attribute_id']
            )
            ->join(
                ['et' => $this->resourceConnection->getTableName('eav_entity_type')],
                'a.entity_type_id = et.entity_type_id',
                []
            )
            ->where('et.entity_type_code = ?', 'catalog_category')
            ->where('a.attribute_code IN (?)', ['is_active', 'include_in_menu']);

        $rows = $connection->fetchPairs($select);

        if (!isset($rows['is_active'], $rows['include_in_menu'])) {
            throw new \RuntimeException(
                'Required category EAV attributes (is_active, include_in_menu) not found.'
            );
        }

        $this->attributeIds = [
            'isActive'      => (int)$rows['is_active'],
            'includeInMenu' => (int)$rows['include_in_menu'],
        ];

        return $this->attributeIds;
    }

    /**
     * Returns the numeric store ID for a given store view code.
     *
     * @param string $storeViewCode
     * @return int
     */
    private function getStoreId(string $storeViewCode): int
    {
        if (!isset($this->storeIdCache[$storeViewCode])) {
            $this->storeIdCache[$storeViewCode] = (int)$this->storeManager->getStore($storeViewCode)->getId();
        }

        return $this->storeIdCache[$storeViewCode];
    }
}
