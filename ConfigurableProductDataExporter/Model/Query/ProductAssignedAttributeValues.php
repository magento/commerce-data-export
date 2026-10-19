<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\ConfigurableProductDataExporter\Model\Query;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\App\ResourceConnection;

/**
 * Bulk resolver for configurable super-attribute values assigned to a configurable product.
 *
 * Replaces the former per-(product, attribute, store) query in
 * {@see \Magento\ConfigurableProductDataExporter\Model\Provider\Product\Options} with two batched queries that are
 * bounded by real data cardinality (children x attributes, children x existing status rows) instead of by the
 * number of store views. The store table is never joined against child rows, which is what previously turned a
 * bounded query into a combinatorial (children x stores) one.
 */
class ProductAssignedAttributeValues
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Resolve the option-value ids assigned to a configurable by at least one of its non-disabled child products.
     *
     * Returned for every requested (productId, attributeId, storeViewCode).
     * The result preserves the semantics of the former per-row query exactly:
     *  - a value is included when at least one child holding it is not disabled for that store view;
     *  - the effective status is the store-level value with fallback to the admin (store_id = 0) value;
     *  - children without a status row (status resolves to null) are excluded;
     *  - when no status attribute exists the result is store-independent (every value passes);
     *  - an unresolvable store code yields an empty set for that store (matches the former inner join on `store`).
     *
     * @param int[] $productIds parent configurable product entity ids
     * @param int[] $attributeIds configurable (super) attribute ids
     * @param string[] $storeViewCodes
     * @param int|null $statusAttributeId
     * @return array [productId][attributeId][storeViewCode] => list of value ids
     */
    public function getAssignedValues(
        array $productIds,
        array $attributeIds,
        array $storeViewCodes,
        ?int $statusAttributeId
    ): array {
        if (!$productIds || !$attributeIds || !$storeViewCodes) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $joinField = $connection->getAutoIncrementField(
            $this->resourceConnection->getTableName('catalog_product_entity')
        );

        // Query A: store-independent (store_id = 0) child values, keyed by parent, attribute and child link id.
        $valuesByParentAttribute = [];
        $childLinkIds = [];
        $valuesSelect = $connection->select()
            ->from(
                ['cpe' => $this->resourceConnection->getTableName('catalog_product_entity')],
                ['parentId' => 'cpe.entity_id']
            )
            ->join(
                ['psl' => $this->resourceConnection->getTableName('catalog_product_super_link')],
                sprintf('psl.parent_id = cpe.%s', $joinField),
                []
            )
            ->join(
                ['cpc' => $this->resourceConnection->getTableName('catalog_product_entity')],
                'cpc.entity_id = psl.product_id',
                []
            )
            ->join(
                ['cpi' => $this->resourceConnection->getTableName('catalog_product_entity_int')],
                sprintf('cpi.%1$s = cpc.%1$s AND cpi.store_id = 0', $joinField),
                [
                    'childLinkId' => sprintf('cpc.%s', $joinField),
                    'attributeId' => 'cpi.attribute_id',
                    'value' => 'cpi.value',
                ]
            )
            ->where('cpe.entity_id IN (?)', $productIds)
            ->where('cpi.attribute_id IN (?)', $attributeIds);

        $cursor = $connection->query($valuesSelect);
        while ($row = $cursor->fetch()) {
            $childLinkId = (int)$row['childLinkId'];
            $valuesByParentAttribute[(int)$row['parentId']][(int)$row['attributeId']][$childLinkId] = $row['value'];
            $childLinkIds[$childLinkId] = $childLinkId;
        }

        if (!$valuesByParentAttribute) {
            return [];
        }

        // Query B: effective status per child, only for status rows that physically exist (no store-table fan-out).
        $storeIdByCode = [];
        $statusByChild = [];
        if ($statusAttributeId !== null) {
            $storeIdByCode = $connection->fetchPairs(
                $connection->select()
                    ->from($this->resourceConnection->getTableName('store'), ['code', 'store_id'])
                    ->where('code IN (?)', $storeViewCodes)
            );
            $statusSelect = $connection->select()
                ->from(
                    ['eav' => $this->resourceConnection->getTableName('catalog_product_entity_int')],
                    [
                        'childLinkId' => sprintf('eav.%s', $joinField),
                        'storeId' => 'eav.store_id',
                        'status' => 'eav.value',
                    ]
                )
                ->where(sprintf('eav.%s IN (?)', $joinField), array_values($childLinkIds))
                ->where('eav.attribute_id = ?', $statusAttributeId)
                ->where('eav.store_id IN (?)', array_merge([0], array_values($storeIdByCode)));

            $cursor = $connection->query($statusSelect);
            while ($row = $cursor->fetch()) {
                $statusByChild[(int)$row['childLinkId']][(int)$row['storeId']] = $row['status'];
            }
        }

        return $this->combine(
            $valuesByParentAttribute,
            $statusByChild,
            $storeIdByCode,
            $storeViewCodes,
            $statusAttributeId
        );
    }

    /**
     * Combine child values with per-store status into the final [productId][attributeId][storeViewCode] map.
     *
     * @param array $valuesByParentAttribute [parentId][attributeId][childLinkId] => value
     * @param array $statusByChild [childLinkId][storeId] => status
     * @param array $storeIdByCode [storeViewCode => storeId]
     * @param string[] $storeViewCodes
     * @param int|null $statusAttributeId
     * @return array
     */
    private function combine(
        array $valuesByParentAttribute,
        array $statusByChild,
        array $storeIdByCode,
        array $storeViewCodes,
        ?int $statusAttributeId
    ): array {
        $result = [];
        foreach ($valuesByParentAttribute as $parentId => $childValuesByAttribute) {
            foreach ($childValuesByAttribute as $attributeId => $childValues) {
                foreach ($storeViewCodes as $storeViewCode) {
                    $values = $this->resolveValuesForStore(
                        $childValues,
                        $statusByChild,
                        isset($storeIdByCode[$storeViewCode]) ? (int) $storeIdByCode[$storeViewCode] : null,
                        $statusAttributeId
                    );
                    if ($values) {
                        $result[$parentId][$attributeId][$storeViewCode] = $values;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Resolve the assigned value ids for a single store view.
     *
     * @param array $childValues [childLinkId => value]
     * @param array $statusByChild [childLinkId][storeId] => status
     * @param int|null $storeId resolved store id, or null when the store code is unknown
     * @param int|null $statusAttributeId
     * @return array
     */
    private function resolveValuesForStore(
        array $childValues,
        array $statusByChild,
        ?int $storeId,
        ?int $statusAttributeId
    ): array {
        $values = [];
        foreach ($childValues as $childLinkId => $value) {
            if ($value === null) {
                continue;
            }
            if ($statusAttributeId === null) {
                $values[$value] = true;
                continue;
            }
            if ($storeId === null) {
                // Unknown store code: matches the former inner join on `store` producing no rows.
                continue;
            }
            $status = $statusByChild[$childLinkId][$storeId] ?? $statusByChild[$childLinkId][0] ?? null;
            if ($status !== null && (int)$status !== Status::STATUS_DISABLED) {
                $values[$value] = true;
            }
        }
        return array_keys($values);
    }
}
