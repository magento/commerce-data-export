<?php
/**
 * Copyright 2023 Adobe
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

namespace Magento\QueryXml\Model\DB;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Mapper for WHERE conditions
 */
class ConditionResolver
{
    private const LINK_FIELD_PATTERN = '/^([\w\\\]+):LinkField$/';
    /**
     * @var array
     */
    private $conditionMap = [
        'eq' => '%1$s = %2$s',
        'neq' => '%1$s != %2$s',
        'like' => '%1$s LIKE %2$s',
        'nlike' => '%1$s NOT LIKE %2$s',
        'in' => '%1$s IN(%2$s)',
        'nin' => '%1$s NOT IN(%2$s)',
        'notnull' => '%1$s IS NOT NULL',
        'null' => '%1$s IS NULL',
        'gt' => '%1$s > %2$s',
        'lt' => '%1$s < %2$s',
        'gteq' => '%1$s >= %2$s',
        'lteq' => '%1$s <= %2$s',
        'finset' => 'FIND_IN_SET(%2$s, %1$s)'
    ];

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $connection;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    private MetadataPool $metadataPool;

    /**
     * ConditionResolver constructor.
     *
     * @param ResourceConnection $resourceConnection
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        MetadataPool $metadataPool
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->metadataPool = $metadataPool;
    }

    /**
     * Returns connection
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private function getConnection()
    {
        if (!$this->connection) {
            $this->connection = $this->resourceConnection->getConnection();
        }
        return $this->connection;
    }

    /**
     * Returns value for condition
     *
     * @param SelectBuilder $selectBuilder
     * @param string $condition
     * @param string $referencedEntity
     * @return mixed|null|string|\Zend_Db_Expr
     */
    private function getValue(SelectBuilder $selectBuilder, $condition, $referencedEntity)
    {
        $value = null;
        $argument = isset($condition['_value']) ? $condition['_value'] : null;
        if (!isset($condition['type'])) {
            $condition['type'] = 'value';
        }

        switch ($condition['type']) {
            case "value":
                $value = $this->getConnection()->quote($argument);
                break;
            case "variable":
                $value = new Expression($argument);
                break;
            case "placeholder":
                $value = '::'. $argument . '::';
                break;
            case "identifier":
                $identifier = explode('.', $argument);
                if (count($identifier) > 1) {
                    $value = $this->getConnection()->quoteIdentifier(
                        $identifier[0] . '.' . $this->resolveIdentifier(
                            $selectBuilder,
                            $identifier[1],
                            $identifier[0]
                        )
                    );
                } else {
                    $value = $this->getConnection()->quoteIdentifier(
                        $referencedEntity ? $referencedEntity . '.' . $argument : $argument
                    );
                }
                break;
        }
        return $value;
    }


    /**
     * @param SelectBuilder $selectBuilder
     * @param string $identifier
     * @param string $tableName
     * @return string
     * @throws \Exception
     */
    private function resolveIdentifier(
        SelectBuilder $selectBuilder,
        string $identifier,
        string $tableName
    ) {
        $queryConfig = $selectBuilder->getQueryConfig();
        $tableName = isset($queryConfig['map'][$tableName])
            ? $this->resourceConnection->getTableName($queryConfig['map'][$tableName])
            : $tableName;
        if (preg_match(self::LINK_FIELD_PATTERN, $identifier, $matches)) {
            $classPath = $matches[1];
            $metadata = $this->metadataPool->getMetadata($classPath);
            $identifier = $metadata->getLinkField();
        }

        return ($identifier !== 'Primary Key') ? $identifier : $this->getConnection()->getAutoIncrementField(
            $tableName
        );
    }

    /**
     * Returns condition for WHERE
     *
     * @param SelectBuilder $selectBuilder
     * @param string $tableName
     * @param array $condition
     * @param null|string $referencedEntity
     * @return string
     */
    private function getCondition(
        SelectBuilder $selectBuilder,
        string $tableAlias,
        $condition,
        $referencedEntity = null
    ) {
        $columns = $selectBuilder->getColumns();
        if (isset($columns[$condition['attribute']])
            && $columns[$condition['attribute']] instanceof Expression
        ) {
            $expression = $columns[$condition['attribute']];
        } else {
            $field = $this->resolveIdentifier($selectBuilder, $condition['attribute'], $tableAlias);
            $expression = $this->getConnection()->quoteIdentifier($tableAlias . '.' . $field);
        }
        return sprintf(
            $this->conditionMap[$condition['operator']],
            $expression,
            $this->getValue($selectBuilder, $condition, $referencedEntity)
        );
    }

    /**
     * Build WHERE condition
     *
     * @param SelectBuilder $selectBuilder
     * @param array $filterConfig
     * @param string $aliasName
     * @param null|string $referencedAlias
     * @return array
     */
    public function getFilter(
        SelectBuilder $selectBuilder,
        array $filterConfig,
        string $tableAlias,
        $referencedAlias = null
    ) {
        $filtersParts = [];
        foreach ($filterConfig as $filter) {
            $glue = $filter['glue'];
            $parts = [];
            foreach ($filter['condition'] as $condition) {
                if (isset($condition['type']) && $condition['type'] == 'variable') {
                    $selectBuilder->setParams(array_merge($selectBuilder->getParams(), [$condition['_value']]));
                }
                $parts[] = $this->getCondition(
                    $selectBuilder,
                    $tableAlias,
                    $condition,
                    $referencedAlias
                );
            }
            if (isset($filter['filter'])) {
                $parts[] = '(' . $this->getFilter(
                    $selectBuilder,
                    $filter['filter'],
                    $tableAlias,
                    $referencedAlias
                ) . ')';
            }
            $filtersParts[] = '(' . implode(' ' . strtoupper($glue) . ' ', $parts) . ')';
        }
        return implode(' OR ', $filtersParts);
    }
}
