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

namespace Magento\QueryXml\Model\DB\Assembler;

use Magento\QueryXml\Model\DB\NameResolver;
use Magento\QueryXml\Model\DB\SelectBuilder;
use Magento\QueryXml\Model\DB\ConditionResolver;
use Magento\QueryXml\Model\DB\ColumnsResolver;
use Magento\Framework\App\ResourceConnection;

/**
 * Assembles JOIN conditions
 */
class JoinAssembler implements AssemblerInterface
{
    /**
     * @var ConditionResolver
     */
    private $conditionResolver;

    /**
     * @var NameResolver
     */
    private $nameResolver;

    /**
     * @var ColumnsResolver
     */
    private $columnsResolver;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ConditionResolver $conditionResolver
     * @param ColumnsResolver $columnsResolver
     * @param NameResolver $nameResolver
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ConditionResolver $conditionResolver,
        ColumnsResolver $columnsResolver,
        NameResolver $nameResolver,
        ResourceConnection $resourceConnection
    ) {
        $this->conditionResolver = $conditionResolver;
        $this->nameResolver = $nameResolver;
        $this->columnsResolver = $columnsResolver;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Assembles JOIN conditions
     *
     * @param SelectBuilder $selectBuilder
     * @param array $queryConfig
     * @return SelectBuilder
     */
    public function assemble(SelectBuilder $selectBuilder, $queryConfig)
    {
        if (!isset($queryConfig['source']['link-source'])) {
            return $selectBuilder;
        }
        $joins = [];
        $filters = $selectBuilder->getFilters();

        $sourceAlias = $this->nameResolver->getAlias($queryConfig['source']);

        foreach ($queryConfig['source']['link-source'] as $join) {
            $joinAlias = $this->nameResolver->getAlias($join);

            $joins[$joinAlias]  = [
                'link-type' => isset($join['link-type']) ? $join['link-type'] : 'left',
                'table' => [
                    $joinAlias => $this->resourceConnection
                        ->getTableName($this->nameResolver->getName($join)),
                ],
                'condition' => $this->conditionResolver->getFilter(
                    $selectBuilder,
                    $join['using'],
                    $joinAlias,
                    $sourceAlias
                )
            ];
            if (isset($join['filter'])) {
                $filters = array_merge(
                    $filters,
                    [
                        $this->conditionResolver->getFilter(
                            $selectBuilder,
                            $join['filter'],
                            $joinAlias,
                            $sourceAlias
                        )
                    ]
                );
            }
            $columns = $this->columnsResolver->getColumns($selectBuilder, isset($join['attribute']) ? $join : []);
            $selectBuilder->setColumns(array_merge($selectBuilder->getColumns(), $columns));
        }
        $selectBuilder->setFilters($filters);
        $selectBuilder->setJoins(array_merge($selectBuilder->getJoins(), $joins));
        return $selectBuilder;
    }
}
