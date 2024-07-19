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

namespace Magento\ScopesDataExporter\Model\Provider;

use Magento\DataExporter\Export\Request\Info;
use Magento\DataExporter\Export\Request\Node;
use Magento\DataExporter\Sql\FieldToPropertyNameConverter;
use Magento\ScopesDataExporter\Model\Query\ScopesCustomerGroupWebsitesQuery;
use Zend_Db_Statement_Exception;

/**
 * Provides a feed data provider for extracting the associated websites for a given customer group.
 */
class ScopesCustomerGroupWebsitesProvider
{
    /**
     * @var ScopesCustomerGroupWebsitesQuery
     */
    private ScopesCustomerGroupWebsitesQuery $queryBuilder;

    /**
     * @var FieldToPropertyNameConverter
     */
    private FieldToPropertyNameConverter $nameConverter;

    /**
     * @param ScopesCustomerGroupWebsitesQuery $queryBuilder
     * @param FieldToPropertyNameConverter $nameConverter
     */
    public function __construct(
        ScopesCustomerGroupWebsitesQuery $queryBuilder,
        FieldToPropertyNameConverter $nameConverter
    ) {
        $this->nameConverter = $nameConverter;
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * Changes from snake_case to camelCase for each key in the record array.
     *
     * @param array $row record passed by reference
     */
    private function changeRecordKeysToCamelCase(array &$row)
    {
        foreach (array_keys($row) as $columnId) {
            $row[$this->nameConverter->toCamelCase($columnId)] = $row[$columnId];
            unset ($row[$columnId]);
        }
    }

    /**
     * Returns the list of websites associated for a given list of customer groups.
     *
     * @param array $values
     * @param Node $node
     * @param Info $info
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function get(array $values, Node $node, Info $info): array
    {
        $result = [];
        $field = $node->getField()['name'];
        $indexField = $this->queryBuilder->getIndexField();
        $dbStatement = $this->queryBuilder->getDbStatement($values);
        while ($row = $dbStatement->fetch()) {
            $this->changeRecordKeysToCamelCase($row);
            $index = [];
            $index[$indexField] = $row[$indexField] ?? null;
            $result[] = array_merge($index, [$field => $row['websiteCode']]);
        }
        return $result;
    }
}
