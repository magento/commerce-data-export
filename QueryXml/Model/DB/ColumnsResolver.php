<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\QueryXml\Model\DB;

use Magento\QueryXml\Model\DB\Assembler\FunctionRenderer\FunctionRendererInterface;

/**
 * Resolves columns names
 */
class ColumnsResolver
{
    /**
     * @var NameResolver
     */
    private $nameResolver;

    /**
     * @var FunctionRendererInterface
     */
    private $functionRenderer;

    /**
     * @param NameResolver $nameResolver
     * @param FunctionRendererInterface $functionRenderer
     */
    public function __construct(
        NameResolver $nameResolver,
        FunctionRendererInterface $functionRenderer
    ) {
        $this->nameResolver = $nameResolver;
        $this->functionRenderer = $functionRenderer;
    }

    /**
     * Set columns list to SelectBuilder
     *
     * @param SelectBuilder $selectBuilder
     * @param array $entityConfig
     * @return array
     */
    public function getColumns(SelectBuilder $selectBuilder, $entityConfig)
    {
        if (!isset($entityConfig['attribute'])) {
            return [];
        }
        $group = [];
        $sort = [];
        $columns = $selectBuilder->getColumns();
        foreach ($entityConfig['attribute'] as $attributeData) {
            $columnAlias = $this->nameResolver->getAlias($attributeData);
            $tableAlias = $this->nameResolver->getAlias($entityConfig);
            $columnName = $this->nameResolver->getName($attributeData);
            if (isset($attributeData['function'])) {
                $expression = $this->functionRenderer->renderFunction($attributeData, $entityConfig, $selectBuilder);
            } else {
                $expression = $tableAlias . '.' . $columnName;
            }
            $columns[$columnAlias] = $expression;
            if (isset($attributeData['group'])) {
                $group[$columnAlias] = $expression;
            }
            if (isset($attributeData['sort'])) {
                $sort[$expression] = $attributeData['sort'];
            }
        }
        $selectBuilder->setGroup(array_merge($selectBuilder->getGroup(), $group));
        $selectBuilder->setSort(array_merge($selectBuilder->getSort(), $sort));
        return $columns;
    }
}
