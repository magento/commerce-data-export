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

declare(strict_types=1);

namespace Magento\QueryXml\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * Class QueryProcessor
 */
class QueryProcessor
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var QueryFactory
     */
    private $queryFactory;

    public function __construct(
        ResourceConnection $resourceConnection,
        QueryFactory $queryFactory
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->queryFactory = $queryFactory;
    }

    /**
     * @param AdapterInterface $connection
     * @param string $sql
     * @param $arguments
     * @return string
     */
    private function processPlaceholders(AdapterInterface $connection, string $sql, $arguments) : string
    {
        // TODO: add support for repeatable variables
        foreach ($arguments as $name => $value) {
            $sql = str_replace(sprintf('::%s::', $name), '?', $sql);
            $sql = $connection->quoteInto($sql, $value);
        }
        return $sql;
    }

    /**
     * @param string $queryName
     * @param array $arguments
     * @return \Zend_Db_Statement_Interface
     */
    public function execute(string $queryName, array $arguments = []) : \Zend_Db_Statement_Interface
    {
        $query = $this->queryFactory->create($queryName);
        $connection = $this->resourceConnection->getConnection($query->getConnectionName());
        $sql = $this->processPlaceholders($connection, $query->getSelect()->assemble(), $arguments);
        return $connection->query($sql);
    }
}
