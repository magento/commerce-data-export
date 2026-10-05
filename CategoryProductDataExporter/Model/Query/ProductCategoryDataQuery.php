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

namespace AdobeCommerce\CategoryProductDataExporter\Model\Query;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Product category query that reads from catalog_category_product source table.
 *
 * Reads directly from the write-side table instead of the index so that data
 * is available when the CategoryIndexerDisabler module suppresses the index.
 * Anchor category inheritance is resolved separately via getAnchorCategoryIds().
 */
class ProductCategoryDataQuery
{
    /** @var array<string, array{id: int, table: string}|null> "entityTypeCode|attributeCode" => metadata */
    private array $attributeMetaCache = [];

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    /**
     * Returns direct product→category assignments from catalog_category_product for one store's root tree.
     *
     * Category-product assignments are website-agnostic, so a product may be assigned to categories in a
     * different root tree than the store view's root. Results are scoped to $rootCategoryPath (categories
     * whose path is a descendant of the store root), mirroring the store-root scoping the category-product
     * index applies, so a store view never exposes categories from a foreign root tree.
     *
     * Only enabled products that are visible somewhere (catalog and/or search) are returned, mirroring the
     * status/visibility filter catalog_category_product_index applies in
     * AbstractAction::getNonAnchorCategoriesSelect() / createAnchorSelect() before writing a row. Both
     * attributes use the store-scoped EAV fallback (per-store value overrides the default store 0).
     * Filtering here excludes non-indexable products before anchor rollup is derived from the result.
     *
     * @param int[] $productIds
     * @param string $rootCategoryPath Path of the store view's root category, e.g. "1/2".
     * @param int $storeId
     * @return Select
     */
    public function getQuery(array $productIds, string $rootCategoryPath, int $storeId): Select
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from(
                ['ccp' => $this->getTable('catalog_category_product')],
                [
                    'productId'       => 'ccp.product_id',
                    'categoryId'      => 'ccp.category_id',
                    'productPosition' => 'ccp.position',
                ]
            )
            ->join(
                ['cce' => $this->getTable('catalog_category_entity')],
                'ccp.category_id = cce.entity_id AND cce.level > 1',
                ['path' => 'cce.path']
            )
            ->join(
                ['cpe' => $this->getTable('catalog_product_entity')],
                'ccp.product_id = cpe.entity_id',
                []
            )
            ->where('ccp.product_id IN (?)', $productIds)
            ->where('cce.path LIKE ?', $rootCategoryPath . '/%');

        $statusValue = $this->joinProductAttribute($select, 'status', $storeId);
        if ($statusValue !== null) {
            $select->where($statusValue . ' = ?', Status::STATUS_ENABLED);
        }

        $visibilityValue = $this->joinProductAttribute($select, 'visibility', $storeId);
        if ($visibilityValue !== null) {
            $select->where($visibilityValue . ' IN (?)', [
                Visibility::VISIBILITY_IN_SEARCH,
                Visibility::VISIBILITY_IN_CATALOG,
                Visibility::VISIBILITY_BOTH,
            ]);
        }

        return $select;
    }

    /**
     * Adds the store-scoped EAV fallback joins for a product int attribute and returns its value expression.
     *
     * Joins the default (store_id=0) and per-store (store_id=$storeId) rows of catalog_product_entity_int
     * for $attributeCode and returns an IFNULL(store, default) expression the caller can filter on, exactly
     * as the core category-product index resolves status/visibility. Returns null if the attribute cannot
     * be resolved, in which case the caller skips the filter (fail-open).
     *
     * @param Select $select Assumes a `cpe` (catalog_product_entity) alias is already joined.
     * @param string $attributeCode
     * @param int $storeId
     * @return string|null IFNULL(...) value expression, or null when the attribute is unknown.
     */
    private function joinProductAttribute(Select $select, string $attributeCode, int $storeId): ?string
    {
        $attribute = $this->resolveAttribute($attributeCode, 'catalog_product');
        if ($attribute === null) {
            return null;
        }

        $connection = $this->resourceConnection->getConnection();
        $linkField = $connection->getAutoIncrementField($this->getTable('catalog_product_entity'));
        $valueTable = $attribute['table'];
        $attributeId = $attribute['id'];
        $defaultAlias = $attributeCode . '_default';
        $storeAlias = $attributeCode . '_store';

        $select->joinLeft(
            [$defaultAlias => $valueTable],
            sprintf(
                '%1$s.%2$s = cpe.%2$s AND %1$s.store_id = 0 AND %1$s.attribute_id = %3$d',
                $defaultAlias,
                $linkField,
                $attributeId
            ),
            []
        )->joinLeft(
            [$storeAlias => $valueTable],
            sprintf(
                '%1$s.%2$s = cpe.%2$s AND %1$s.store_id = %3$d AND %1$s.attribute_id = %4$d',
                $storeAlias,
                $linkField,
                $storeId,
                $attributeId
            ),
            []
        );

        return (string)$connection->getIfNullSql($storeAlias . '.value', $defaultAlias . '.value');
    }

    /**
     * Returns the path of the given store view's root category (e.g. "1/2"), or '' if it cannot be resolved.
     *
     * The store view belongs to a store group whose root_category_id defines the tree the store exposes.
     *
     * @param int $storeId
     * @return string
     */
    public function getRootCategoryPath(int $storeId): string
    {
        $connection = $this->resourceConnection->getConnection();

        return (string)$connection->fetchOne(
            $connection->select()
                ->from(['s' => $this->getTable('store')], [])
                ->join(
                    ['sg' => $this->getTable('store_group')],
                    's.group_id = sg.group_id',
                    []
                )
                ->join(
                    ['cce' => $this->getTable('catalog_category_entity')],
                    'cce.entity_id = sg.root_category_id',
                    ['path']
                )
                ->where('s.store_id = ?', $storeId)
        );
    }

    /**
     * Returns IDs of anchor categories (is_anchor=1) among the given category IDs.
     *
     * @param int[] $categoryIds
     * @param int   $storeId
     * @return int[]
     */
    public function getAnchorCategoryIds(array $categoryIds, int $storeId): array
    {
        return $this->getCategoryIdsWithFlag($categoryIds, $storeId, 'is_anchor');
    }

    /**
     * Returns IDs of active categories (is_active=1) among the given category IDs.
     *
     * @param int[] $categoryIds
     * @param int   $storeId
     * @return int[]
     */
    public function getActiveCategoryIds(array $categoryIds, int $storeId): array
    {
        return $this->getCategoryIdsWithFlag($categoryIds, $storeId, 'is_active');
    }

    /**
     * Returns category IDs whose given boolean EAV attribute resolves to 1 for the store.
     *
     * Applies store-scoped EAV override: per-store value (store_id=$storeId) takes
     * precedence over the default (store_id=0).
     *
     * Compatible with both CE and EE: joins on entity_id which exists in all editions.
     *
     * @param int[] $categoryIds
     * @param int $storeId
     * @param string $attributeCode
     * @return int[]
     */
    private function getCategoryIdsWithFlag(array $categoryIds, int $storeId, string $attributeCode): array
    {
        if (empty($categoryIds)) {
            return [];
        }

        $attribute = $this->resolveAttribute($attributeCode, 'catalog_category');
        if ($attribute === null) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $linkField = $connection->getAutoIncrementField($this->getTable('catalog_category_entity'));
        $rows = $connection->fetchAll(
            $connection->select()
                ->from(
                    ['ccei' => $attribute['table']],
                    ['store_id', 'value']
                )
                ->join(
                    ['cce' => $this->getTable('catalog_category_entity')],
                    sprintf('ccei.%1$s = cce.%1$s', $linkField),
                    ['entity_id' => 'cce.entity_id']
                )
                ->where('ccei.attribute_id = ?', $attribute['id'])
                ->where('cce.entity_id IN (?)', $categoryIds)
                ->where('ccei.store_id IN (?)', array_unique([0, $storeId]))
        );

        // The WHERE clause admits only store_id 0 and $storeId, so at most two rows exist per
        // entity_id - index by store_id and pick the per-store value over the default in PHP,
        // no SQL-side ordering needed to make the precedence deterministic.
        $valuesByEntity = [];
        foreach ($rows as $row) {
            $valuesByEntity[(int)$row['entity_id']][(int)$row['store_id']] = (int)$row['value'];
        }

        $resolved = [];
        foreach ($valuesByEntity as $id => $values) {
            $resolved[$id] = $values[$storeId] ?? $values[0] ?? 0;
        }

        return array_keys(array_filter($resolved, static fn(int $v): bool => $v === 1));
    }

    /**
     * Resolves and caches the id and backend value table of an EAV attribute by code and entity type.
     *
     * The value table is derived from the attribute's own metadata rather than hardcoded: an explicit
     * backend_table wins, otherwise it is the entity's value table for the attribute's backend_type
     * (e.g. catalog_product_entity + "_int" => catalog_product_entity_int). Returns null when the
     * attribute is unknown or backed by a static column (no separate value table to join).
     *
     * @param string $attributeCode
     * @param string $entityTypeCode
     * @return array{id: int, table: string}|null
     */
    private function resolveAttribute(string $attributeCode, string $entityTypeCode): ?array
    {
        $cacheKey = $entityTypeCode . '|' . $attributeCode;
        if (array_key_exists($cacheKey, $this->attributeMetaCache)) {
            return $this->attributeMetaCache[$cacheKey];
        }

        $connection = $this->resourceConnection->getConnection();
        $row = $connection->fetchRow(
            $connection->select()
                ->from(['ea' => $this->getTable('eav_attribute')], ['attribute_id', 'backend_type', 'backend_table'])
                ->join(
                    ['eet' => $this->getTable('eav_entity_type')],
                    'ea.entity_type_id = eet.entity_type_id',
                    ['entity_table']
                )
                ->where('ea.attribute_code = ?', $attributeCode)
                ->where('eet.entity_type_code = ?', $entityTypeCode)
        );

        if (!$row || empty($row['attribute_id']) || $row['backend_type'] === 'static') {
            return $this->attributeMetaCache[$cacheKey] = null;
        }

        $valueTable = $row['backend_table'] ?: $row['entity_table'] . '_' . $row['backend_type'];

        return $this->attributeMetaCache[$cacheKey] = [
            'id' => (int)$row['attribute_id'],
            'table' => $this->getTable($valueTable),
        ];
    }

    /**
     * Returns the real (prefixed) table name for the given logical table name.
     *
     * @param string $tableName
     * @return string
     */
    private function getTable(string $tableName): string
    {
        return $this->resourceConnection->getTableName($tableName);
    }
}
