<?php
/**
 * Copyright 2024 Adobe
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

namespace Magento\CatalogDataExporter\Model\Query\Eav;

use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\Store;

/**
 * Generic for build Select object to fetch eav attributes for provided entity type
 */
class EavAttributeQueryBuilder implements EavAttributeQueryBuilderInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var string
     */
    private $entityType;

    /**
     * List of attributes that need to be added/removed to fetch
     *
     * @var array
     */
    private $linkedAttributes;

    /**
     * @var array
     */
    private const SUPPORTED_BACKEND_TYPES = [
        'int',
        'decimal',
        'text',
        'varchar',
        'datetime',
    ];

    /**
     * @var int[]
     */
    private $entityTypeIdMap;

    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * @var array
     */
    private array $attributesMetadata = [];
    private array $storeCodeToStoreIdMap = [];

    /**
     * @param string $entityType
     * @param ResourceConnection $resourceConnection
     * @param MetadataPool $metadataPool
     * @param Config $eavConfig
     * @param array $linkedAttributes
     */
    public function __construct(
        string $entityType,
        ResourceConnection $resourceConnection,
        MetadataPool $metadataPool,
        Config $eavConfig,
        array $linkedAttributes = []
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->metadataPool = $metadataPool;
        $this->entityType = $entityType;
        $this->linkedAttributes = $linkedAttributes;
        $this->eavConfig = $eavConfig;
    }

    /**
     * @inheritDoc
     */
    public function build(array $entityIds, array $attributes, string $storeCode): Select
    {
        /** @var EntityMetadataInterface $metadata */
        $metadata = $this->metadataPool->getMetadata($this->entityType);
        $entityTableName = $metadata->getEntityTable();

        $connection = $this->resourceConnection->getConnection();

        $eavAttributes = $this->getEavAttributeCodes($attributes);

        $eavAttributesMetaData = $this->getAttributesMetaData($connection, $entityTableName, $eavAttributes);

        if ($eavAttributesMetaData) {
            $select = $this->getEavAttributes(
                $connection,
                $metadata,
                $entityIds,
                $eavAttributesMetaData,
                $entityTableName,
                $storeCode
            );
        } else {
            $select = $this->getAttributesFromEntityTable(
                $connection,
                $entityIds,
                $entityTableName
            );
        }

        return $select;
    }

    /**
     * Form and return query to get entity $entityTableAttributes for given $entityIds
     *
     * @param AdapterInterface $connection
     * @param array $entityTableAttributes
     * @param int[] $entityIds
     * @param string $entityTableName
     * @return Select
     */
    private function getAttributesFromEntityTable(
        AdapterInterface $connection,
        array $entityIds,
        string $entityTableName
    ): Select {
        return $connection->select()
            ->from(['e' => $entityTableName])
            ->where('e.entity_id IN (?)', $entityIds);
    }

    /**
     * Returns eav attributes metadata in format
     *  [
     *    '<eav table name>' => [
     *        'attribute_code' => 'attribute_id'
     *        ...
     *    ], ...
     *  ]
     *
     * @param AdapterInterface $connection
     * @param string $entityTableName
     * @param array $eavAttributeCodes
     * @return array
     * @throws \Exception
     */
    private function getAttributesMetaData(
        AdapterInterface $connection,
        string $entityTableName,
        array $eavAttributeCodes
    ): array {
        $missingAttributeCodes = array_diff($eavAttributeCodes, array_keys($this->attributesMetadata));
        if (!empty($missingAttributeCodes)) {
            $attributeMetadataTable = $this->resourceConnection->getTableName('eav_attribute');

            $eavAttributeIdsSelect = $connection->select()
                ->from(['a' => $attributeMetadataTable], ['attribute_id', 'backend_type', 'attribute_code'])
                ->where('a.attribute_code IN (?)', $missingAttributeCodes)
                ->where('a.entity_type_id = ?', $this->getEntityTypeId());

            $fetchedData = $connection->fetchAssoc($eavAttributeIdsSelect);
            foreach ($fetchedData as $data) {
                if (\in_array($data['backend_type'], self::SUPPORTED_BACKEND_TYPES, true)) {
                    $data['table'] = \sprintf('%s_%s', $entityTableName, $data['backend_type']);
                }
                $this->attributesMetadata[$data['attribute_code']] = $data;
            }
        }
        $attributes = \array_intersect_key($this->attributesMetadata, $eavAttributeCodes);
        $result = [];
        foreach ($attributes as $attribute) {
            if (!isset($attribute['table'])) {
                continue;
            }
            $result[$attribute['table']][$attribute['attribute_code']] = $attribute['attribute_id'];
        }
        return $result;
    }

    /**
     * Form and return query to get eav entity $attributes for given $entityIds.
     *
     * @param AdapterInterface $connection
     * @param EntityMetadataInterface $metadata
     * @param array $entityTableAttributes
     * @param int[] $entityIds
     * @param array $eavAttributesMetaData
     * @param string $entityTableName
     * @param string $storeCode
     * @return Select
     * @throws \Zend_Db_Select_Exception
     */
    private function getEavAttributes(
        AdapterInterface $connection,
        EntityMetadataInterface $metadata,
        array $entityIds,
        array $eavAttributesMetaData,
        string $entityTableName,
        string $storeCode
    ): Select {
        $selects = [];
        $linkField = $metadata->getLinkField();
        foreach ($eavAttributesMetaData as $attributeTable => $eavAttributes) {
            $eavAttributeTable = $this->resourceConnection->getTableName($attributeTable);

            $valueExpr = stripos($eavAttributeTable, '_int', -4)
                ? new \Zend_Db_Expr('CAST(eav.value as CHAR)')
                : 'eav.value';

            $selects[] = $connection->select()
                ->from(['e' => $entityTableName], ['entity_id'])
                ->join(
                    ['eav' => $eavAttributeTable],
                    \sprintf('e.%1$s = eav.%1$s', $linkField) .
                    $connection->quoteInto(' AND eav.attribute_id IN (?)', array_values($eavAttributes)) .
                    $connection->quoteInto(
                        ' AND eav.store_id IN (?)',
                        [Store::DEFAULT_STORE_ID, $this->getStoreId($storeCode)]
                    ),
                    ['store_id']
                )
                ->join(['a' => $this->resourceConnection->getTableName('eav_attribute')],
                    'eav.attribute_id = a.attribute_id',
                    ['attribute_code']
                )
                ->where('e.entity_id IN (?)', $entityIds)
                ->columns(
                    [
                        'value' => $valueExpr
                    ]
                );
        }

        return $connection->select()->union($selects, Select::SQL_UNION_ALL);
    }

    /**
     * @param string $storeCode
     * @return int
     */
    private function getStoreId(string $storeCode): int
    {
        if (!isset($this->storeCodeToStoreIdMap[$storeCode])) {
            $connection = $this->resourceConnection->getConnection();

            $storeId = (int)$connection->fetchOne(
                $connection->select()
                    ->from(['s' => $this->resourceConnection->getTableName('store')], ['store_id'])
                    ->where('s.code = ?', $storeCode)
            );
            $this->storeCodeToStoreIdMap[$storeCode] = $storeId;
        }
        return $this->storeCodeToStoreIdMap[$storeCode];
    }

    /**
     * Get EAV attribute codes
     * Remove attributes from exclude list
     * Add linked attributes to output
     *
     * @param array $attributes
     * @param array $entityTableAttributes
     * @return array
     */
    private function getEavAttributeCodes($attributes): array
    {
        $unusedAttributeList = [];
        $newAttributes = [];
        foreach ($this->linkedAttributes as $attribute => $linkedAttributes) {
            if (null === $linkedAttributes) {
                $unusedAttributeList[] = $attribute;
            } elseif (\is_array($linkedAttributes) && \in_array($attribute, $attributes, true)) {
                $newAttributes[] = $linkedAttributes;
            }
        }
        $attributes = \array_diff($attributes, $unusedAttributeList);

        return \array_unique(\array_merge($attributes, ...$newAttributes));
    }

    /**
     * Retrieve entity type id
     *
     * @return int
     * @throws \Exception
     */
    private function getEntityTypeId(): int
    {
        if (!isset($this->entityTypeIdMap[$this->entityType])) {
            $this->entityTypeIdMap[$this->entityType] = (int)$this->eavConfig->getEntityType(
                $this->metadataPool->getMetadata($this->entityType)->getEavEntityType()
            )->getId();
        }

        return $this->entityTypeIdMap[$this->entityType];
    }
}
