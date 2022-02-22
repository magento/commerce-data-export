<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\QueryXml\Model;

use Magento\Framework\DB\Select;

/**
 * Query object, contains SQL statement, information about connection, query arguments
 */
class Query implements \JsonSerializable
{
    /**
     * @var Select
     */
    private $select;

    /**
     * @var \Magento\QueryXml\Model\SelectHydrator
     */
    private $selectHydrator;

    /**
     * @var string
     */
    private $connectionName;

    /**
     * @var array
     */
    private $config;

    /**
     * Query constructor.
     *
     * @param Select $select
     * @param SelectHydrator $selectHydrator
     * @param string $connectionName
     * @param array $config
     */
    public function __construct(
        Select $select,
        SelectHydrator $selectHydrator,
        $connectionName,
        $config
    ) {
        $this->select = $select;
        $this->connectionName = $connectionName;
        $this->selectHydrator = $selectHydrator;
        $this->config = $config;
    }

    /**
     * Get database select object
     *
     * @return Select
     */
    public function getSelect()
    {
        return $this->select;
    }

    /**
     * Get database connection name
     *
     * @return string
     */
    public function getConnectionName()
    {
        return $this->connectionName;
    }

    /**
     * Get array of configurations
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * {@inerhitDoc}
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'connectionName' => $this->getConnectionName(),
            'select_parts' => $this->selectHydrator->extract($this->getSelect()),
            'config' => $this->getConfig()
        ];
    }
}
