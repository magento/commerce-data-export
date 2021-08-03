<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QueryXml\Model\DB\Assembler\FunctionRenderer;

use Magento\Framework\DB\Sql\ColumnValueExpression;
use Magento\QueryXml\Model\DB\SelectBuilder;

/**
 * Interface FunctionRendererInterface
 */
interface FunctionRendererInterface
{
    /**
     * Function render logic
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
    ) : ColumnValueExpression;
}
