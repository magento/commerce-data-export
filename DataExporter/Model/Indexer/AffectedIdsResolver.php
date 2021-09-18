<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
