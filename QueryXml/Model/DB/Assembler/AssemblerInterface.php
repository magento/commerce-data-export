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
