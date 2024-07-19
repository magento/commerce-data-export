<?php
/**
 * Copyright 2021 Adobe
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
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\Expression;

/**
 * Responsible for Select object creation, works as a builder. Returns Select as result;
 *
 * Used in SQL assemblers.
 */
class SelectBuilder
{
    /**
     * @var array
     */
    private $queryConfig;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var string
     */
    private $connectionName;

    /**
     * @var array
     */
    private $from;

    /**
     * @var array
     */
    private $group = [];

    /**
     * @var array
     */
    private $columns = [];

    /**
     * @var array
     */
    private $filters = [];

    /**
     * @var array
     */
    private $joins = [];

    /**
     * @var array
     */
    private $params = [];

    /**
     * @var array
     */
    private $having = [];

    /**
     * @var array
     */
    private $sort = [];

    /**
     * SelectBuilder constructor.
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        array $queryConfig = []
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->queryConfig = $queryConfig;
    }

    /**
     * Get join condition
     *
     * @return array
     */
    public function getJoins()
    {
        return $this->joins;
    }

    /**
     * Set joins conditions
     *
     * @param array $joins
     * @return $this
     */
    public function setJoins($joins)
    {
        $this->joins = $joins;

        return $this;
    }

    /**
     * Get connection name
     *
     * @return string
     */
    public function getConnectionName()
    {
        return $this->connectionName;
    }

    /**
     * Set connection name
     *
     * @param string $connectionName
     * @return $this
     */
    public function setConnectionName($connectionName)
    {
        $this->connectionName = $connectionName;

        return $this;
    }

    /**
     * Get columns
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Set columns
     *
     * @param array $columns
     * @return $this
     */
    public function setColumns($columns)
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * Get filters
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Set filters
     *
     * @param array $filters
     * @return $this
     */
    public function setFilters($filters)
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * Get from condition
     *
     * @return array
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Set from condition
     *
     * @param array $from
     * @return $this
     */
    public function setFrom($from)
    {
        $this->from = $from;

        return $this;
    }

    /**
     * Process JOIN conditions
     *
     * @param Select $select
     * @param array $joinConfig
     * @return Select
     */
    private function processJoin(Select $select, $joinConfig)
    {
        switch ($joinConfig['link-type']) {
            case 'left':
                $select->joinLeft($joinConfig['table'], $joinConfig['condition'], []);
                break;
            case 'inner':
                $select->joinInner($joinConfig['table'], $joinConfig['condition'], []);
                break;
            case 'right':
                $select->joinRight($joinConfig['table'], $joinConfig['condition'], []);
                break;
        }
        return $select;
    }

    /**
     * Creates Select object
     *
     * @return Select
     */
    public function create()
    {
        $connection = $this->resourceConnection->getConnection($this->getConnectionName());
        $select = $connection->select();
        $select->from($this->getFrom(), []);
        $select->columns($this->getColumns());
        foreach ($this->getFilters() as $filter) {
            $select->where($filter);
        }
        foreach ($this->getJoins() as $joinConfig) {
            $select = $this->processJoin($select, $joinConfig);
        }
        if (!empty($this->getGroup())) {
            $select->group($this->getGroup());
        }
        if (!empty($this->getSort())) {
            $sort = [];
            foreach ($this->getSort() as $expression => $direction) {
                $sort[] = $expression . ' ' . $direction;
            }
            $select->order($sort);
        }
        return $select;
    }

    /**
     * Returns group
     *
     * @return array
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Set group
     *
     * @param array $group
     * @return $this
     */
    public function setGroup($group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Returns sort
     *
     * @return array
     */
    public function getSort(): array
    {
        return $this->sort;
    }

    /**
     * Set sort
     *
     * @param $sort
     * @return $this
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Get parameters
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set parameters
     *
     * @param array $params
     * @return $this
     */
    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Get having condition
     *
     * @return array
     */
    public function getHaving()
    {
        return $this->having;
    }

    /**
     * Set having condition
     *
     * @param array $having
     * @return $this
     */
    public function setHaving($having)
    {
        $this->having = $having;

        return $this;
    }

    public function getQueryConfig() : array
    {
        return $this->queryConfig;
    }
}
