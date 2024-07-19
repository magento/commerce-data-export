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
