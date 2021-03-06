<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\QueryXml\Model\DB;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\ColumnValueExpression;

/**
 * Resolves columns names
 */
class ColumnsResolver
{
    /**
     * @var NameResolver
     */
    private $nameResolver;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $connection;

    /**
     * ColumnsResolver constructor.
     *
     * @param NameResolver $nameResolver
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        NameResolver $nameResolver,
        ResourceConnection $resourceConnection
    ) {
        $this->nameResolver = $nameResolver;
        $this->resourceConnection = $resourceConnection;
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
     * Set columns list to SelectBuilder
     *
     * @param SelectBuilder $selectBuilder
     * @param array $entityConfig
     * @return array
     */
    public function getColumns(SelectBuilder $selectBuilder, $entityConfig)
    {
        if (!isset($entityConfig['attribute'])) {
            return [];
        }
        $group = [];
        $sort = [];
        $columns = $selectBuilder->getColumns();
        foreach ($entityConfig['attribute'] as $attributeData) {
            $columnAlias = $this->nameResolver->getAlias($attributeData);
            $tableAlias = $this->nameResolver->getAlias($entityConfig);
            $columnName = $this->nameResolver->getName($attributeData);
            if (isset($attributeData['function'])) {
                $prefix = '';
                if (!empty($attributeData['distinct'])) {
                    $prefix = ' DISTINCT ';
                }
                $expression = new ColumnValueExpression(
                    strtoupper($attributeData['function']) . '(' . $prefix
                    . $this->getConnection()->quoteIdentifier($tableAlias . '.' . $columnName)
                    . ')'
                );
            } else {
                $expression = $tableAlias . '.' . $columnName;
            }
            $columns[$columnAlias] = $expression;
            if (isset($attributeData['group'])) {
                $group[$columnAlias] = $expression;
            }
            if (isset($attributeData['sort'])) {
                $sort[$expression] = $attributeData['sort'];
            }
        }
        $selectBuilder->setGroup(array_merge($selectBuilder->getGroup(), $group));
        $selectBuilder->setSort(array_merge($selectBuilder->getSort(), $sort));
        return $columns;
    }
}
