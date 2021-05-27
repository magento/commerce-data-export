<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\QueryXml\Model\DB\Assembler;

use Magento\QueryXml\Model\DB\NameResolver;
use Magento\QueryXml\Model\DB\SelectBuilder;
use Magento\QueryXml\Model\DB\ConditionResolver;

/**
 * Assembles WHERE conditions
 */
class FilterAssembler implements AssemblerInterface
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
     * FilterAssembler constructor.
     *
     * @param ConditionResolver $conditionResolver
     * @param NameResolver $nameResolver
     */
    public function __construct(
        ConditionResolver $conditionResolver,
        NameResolver $nameResolver
    ) {
        $this->conditionResolver = $conditionResolver;
        $this->nameResolver = $nameResolver;
    }

    /**
     * Assembles WHERE conditions
     *
     * @param SelectBuilder $selectBuilder
     * @param array $queryConfig
     * @return SelectBuilder
     */
    public function assemble(SelectBuilder $selectBuilder, $queryConfig)
    {
        if (!isset($queryConfig['source']['filter'])) {
            return $selectBuilder;
        }
        $filters = $this->conditionResolver->getFilter(
            $selectBuilder,
            $queryConfig['source']['filter'],
            $this->nameResolver->getAlias($queryConfig['source'])
        );
        $selectBuilder->setFilters(array_merge_recursive($selectBuilder->getFilters(), [$filters]));
        return $selectBuilder;
    }
}
