<?php

/**
 * Copyright 2022 Adobe
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
