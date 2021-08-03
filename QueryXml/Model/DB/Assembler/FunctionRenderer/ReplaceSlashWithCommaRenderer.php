<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QueryXml\Model\DB\Assembler\FunctionRenderer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\ColumnValueExpression;
use Magento\QueryXml\Model\DB\NameResolver;
use Magento\QueryXml\Model\DB\SelectBuilder;

/**
 * Implements REPLACE function with predefined arguments
 */
class ReplaceSlashWithCommaRenderer implements FunctionRendererInterface
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
        $connection = $this->resourceConnection->getConnection();
        return new ColumnValueExpression(
            sprintf(
                "REPLACE(%s, %s, %s)",
                $connection->quoteIdentifier($tableAlias . '.' . $columnName),
                "'\'",
                "','"
            )
        );
    }
}
