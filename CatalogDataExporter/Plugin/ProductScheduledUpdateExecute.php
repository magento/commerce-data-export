<?php
/**
 * Copyright 2024 Adobe
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

namespace Magento\CatalogDataExporter\Plugin;

use Magento\Framework\Indexer\IndexerRegistry;

class ProductScheduledUpdateExecute
{
    /**
     * @var IndexerRegistry
     */
    private IndexerRegistry $indexerRegistry;

    /**
     * @var array
     */
    private array $indexersPool;

    /**
     * @param IndexerRegistry $indexerRegistry
     * @param array $indexersPool
     */
    public function __construct(
        IndexerRegistry $indexerRegistry,
        array $indexersPool
    ) {
        $this->indexerRegistry = $indexerRegistry;
        $this->indexersPool = $indexersPool;
    }

    /**
     * Reindex feeds after scheduled update execution
     *
     * @param $subject
     * @param void $result
     * @param array $entityIds
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute($subject, $result, array $entityIds)
    {
        if ($entityIds) {
            foreach ($this->indexersPool as $indexerId) {
                $indexer = $this->indexerRegistry->get($indexerId);
                $indexer->reindexList($entityIds);
            }
        }

        return $result;
    }
}
