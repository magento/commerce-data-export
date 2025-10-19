<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Plugin;

use Magento\Framework\Indexer\IndexerRegistry;

class CategoryScheduledUpdateExecute
{
    /**
     * @var IndexerRegistry
     */
    private IndexerRegistry $indexerRegistry;

    /**
     * @var string
     */
    private const CATEGORY_FEED_INDEXER = 'catalog_data_exporter_categories';

    /**
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(
        IndexerRegistry $indexerRegistry,
    ) {
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * Reindex category feed after scheduled update execution
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
            $indexer = $this->indexerRegistry->get(self::CATEGORY_FEED_INDEXER);
            $indexer->reindexList($entityIds);
        }

        return $result;
    }
}
