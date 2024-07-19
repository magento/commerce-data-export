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

use Magento\QueryXml\Model\DB\ColumnsResolver;
use Magento\QueryXml\Model\DB\SelectBuilder;
use Magento\QueryXml\Model\DB\NameResolver;
use Magento\Framework\App\ResourceConnection;

/**
 * Assembles FROM condition
 */
class FromAssembler implements AssemblerInterface
{
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
     * @param NameResolver $nameResolver
     * @param ColumnsResolver $columnsResolver
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        NameResolver $nameResolver,
        ColumnsResolver $columnsResolver,
        ResourceConnection $resourceConnection
    ) {
        $this->nameResolver = $nameResolver;
        $this->columnsResolver = $columnsResolver;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Assembles FROM condition
     *
     * @param SelectBuilder $selectBuilder
     * @param array $queryConfig
     * @return SelectBuilder
     */
    public function assemble(SelectBuilder $selectBuilder, $queryConfig)
    {
        $selectBuilder->setFrom(
            [
                $this->nameResolver->getAlias($queryConfig['source']) =>
                    $this->resourceConnection
                        ->getTableName($this->nameResolver->getName($queryConfig['source'])),
            ]
        );
        $columns = $this->columnsResolver->getColumns($selectBuilder, $queryConfig['source']);
        $selectBuilder->setColumns(array_merge($selectBuilder->getColumns(), $columns));
        return $selectBuilder;
    }
}
