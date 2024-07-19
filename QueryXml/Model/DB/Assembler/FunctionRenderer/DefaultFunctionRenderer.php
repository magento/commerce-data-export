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
declare(strict_types=1);

namespace Magento\QueryXml\Model\DB\Assembler\FunctionRenderer;

use Magento\Framework\DB\Sql\ColumnValueExpression;
use Magento\QueryXml\Model\DB\SelectBuilder;
use Magento\QueryXml\Model\DB\NameResolver;
use Magento\Framework\App\ResourceConnection;

/**
 * Default functions renderer, should be used if no specific renderer declared
 */
class DefaultFunctionRenderer implements FunctionRendererInterface
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
     * {@inheritdoc}
     *
     * @param array $attributeInfo
     * @param array $entityInfo
     * @param SelectBuilder $builder
     * @return ColumnValueExpression
     */
    public function renderFunction(
        array $attributeInfo,
        array $entityInfo,
        SelectBuilder $builder
    ) : ColumnValueExpression {
        $tableAlias = $this->nameResolver->getAlias($entityInfo);
        $columnName = $this->nameResolver->getName($attributeInfo);
        $prefix = '';
        if (!empty($attributeInfo['distinct'])) {
            $prefix = ' DISTINCT ';
        }
        $connection = $this->resourceConnection->getConnection();
        return new ColumnValueExpression(
            strtoupper($attributeInfo['function']) . '(' . $prefix
            . $connection->quoteIdentifier($tableAlias . '.' . $columnName)
            . ')'
        );
    }
}
