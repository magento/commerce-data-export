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
declare(strict_types=1);

namespace Magento\ScopesDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Zend_Db_Statement_Interface;

/**
 * Query builder to obtain the list of websites associated to a given list of customer groups.
 * In case there are no customer groups to filter by, it will build a sql query to search for all customer groups.
 */
class ScopesCustomerGroupWebsitesQuery
{
    /**
     * SQL Query template to extract the websites associated to a list of customer groups,
     * filtering out the admin website.
     */
    private const SQL_QUERY_TEMPLATE = "
        with scope_websites_v as (
            select ws.website_id, ws.code as website_code
            from {{store_website}} ws
            where ws.website_id <> 0
        ), scope_customer_groups_v as (
            select cg.customer_group_id, cg.customer_group_code
            from {{customer_group}} cg
            where cg.customer_group_id in (::customerGroupId::)
        )
        select
            scg.customer_group_id,
            sws.website_code
        from
            scope_websites_v sws
        cross join
            scope_customer_groups_v scg
        where not exists (
            select 1
            from {{customer_group_excluded_website}} sgew
            where sgew.website_id = sws.website_id
            and sgew.customer_group_id = scg.customer_group_id
        );
    ";

    /**
     * Query condition to remove in case there are no arguments for placeholders.
     */
    private const PLACEHOLDER_STATEMENT_TO_CLEAN = "where cg.customer_group_id in (::customerGroupId::)";

    /**
     * Tables required in the query. Some magento installs require a table prefix.
     */
    private const TABLE_NAME_PLACEHOLDERS = [
        "{{store_website}}",
        "{{customer_group}}",
        "{{customer_group_excluded_website}}"
    ];

    /**
     * Column Id to filter data and prepare query arguments.
     */
    private const CUSTOMER_GROUP_ID = 'customerGroupId';

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var AdapterInterface|null
     */
    private AdapterInterface $connection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Creates a db statement to obtain the list of websites associated to a given list of customer groups.
     * In case there are none to filter by, it will build a sql query to search for all customer groups.
     *
     * @param array $data
     * @return Zend_Db_Statement_Interface
     */
    public function getDbStatement(array $data = []): Zend_Db_Statement_Interface
    {
        $sql = $this->buildSqlQuery($data);
        return $this->getConnection()->query($sql);
    }

    /**
     * @return string
     */
    public function getIndexField(): string
    {
        return self::CUSTOMER_GROUP_ID;
    }

    /**
     * Lazy loads a database connection.
     *
     * @return AdapterInterface
     */
    private function getConnection(): AdapterInterface
    {
        if(!isset($this->connection)) {
            $this->connection = $this->resourceConnection->getConnection();
        }
        return $this->connection;
    }

    /**
     * Process the query string to change placeholders for query parameters.
     *
     * @param array $data for query filtering and parameter binding.
     * @return string
     */
    private function buildSqlQuery(array $data): string
    {
        $sql = self::SQL_QUERY_TEMPLATE;
        foreach(self::TABLE_NAME_PLACEHOLDERS as $tableNamePlaceholder) {
            $tableName = $this->resourceConnection->getTableName(
                str_replace(["{{","}}"], "", $tableNamePlaceholder)
            );
            $sql = str_replace($tableNamePlaceholder, $tableName, $sql);
        }
        $arguments[self::CUSTOMER_GROUP_ID] = array_column($data, self::CUSTOMER_GROUP_ID);
        if(empty($arguments) || empty($arguments[self::CUSTOMER_GROUP_ID])) {
            $sql = str_replace(self::PLACEHOLDER_STATEMENT_TO_CLEAN, "", $sql);
        } else {
            foreach ($arguments as $name => $value) {
                $sql = str_replace(sprintf('::%s::', $name), '?', $sql);
                $sql = $this->getConnection()->quoteInto($sql, $value);
            }
        }
        return $sql;
    }
}
