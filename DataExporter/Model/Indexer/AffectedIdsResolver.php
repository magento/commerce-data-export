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

namespace Magento\DataExporter\Model\Indexer;

use Magento\QueryXml\Model\QueryProcessor;

/**
 * Returns parent IDs for simple products
 */
class AffectedIdsResolver implements AffectedIdsResolverInterface
{
    /**
     * @var QueryProcessor
     */
    private $queryProcessor;

    /**
     * @var array
     */
    private $queryNames;

    /**
     * @var string
     */
    private $idPlaceholder;

    /**
     * @param QueryProcessor $queryProcessor
     * @param array $queryNames
     * @param string $idPlaceholder
     */
    public function __construct(
        QueryProcessor $queryProcessor,
        array $queryNames,
        string $idPlaceholder
    ) {
        $this->queryProcessor = $queryProcessor;
        $this->queryNames = $queryNames;
        $this->idPlaceholder = $idPlaceholder;
    }

    /**
     * @inheritDoc
     *
     * @param string[] $ids
     * @return string[]
     * @throws \Zend_Db_Statement_Exception
     */
    public function getAllAffectedIds(array $ids): array
    {
        $output = $ids;
        $arguments = [$this->idPlaceholder => $ids];
        foreach ($this->queryNames as $queryName) {
            $result = $this->queryProcessor->execute($queryName, $arguments);
            while ($id = $result->fetchColumn()) {
                $output[] = $id;
            }
        }
        return $output;
    }
}
