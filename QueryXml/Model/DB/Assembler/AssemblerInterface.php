<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 */
namespace Magento\QueryXml\Model\DB\Assembler;

use Magento\QueryXml\Model\DB\SelectBuilder;

/**
 * Interface AssemblerInterface
 *
 * Introduces family of SQL assemblers
 * Each assembler populates SelectBuilder with config information
 * @see usage examples at \Magento\QueryXml\Model\QueryFactory
 */
interface AssemblerInterface
{
    /**
     * Assemble SQL statement
     *
     * @param SelectBuilder $selectBuilder
     * @param array $queryConfig
     * @return SelectBuilder
     */
    public function assemble(SelectBuilder $selectBuilder, $queryConfig);
}
