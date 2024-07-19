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

namespace Magento\ProductReviewDataExporter\Model\Indexer;

use Magento\Framework\Indexer\IndexerRegistry;

/**
 * Action responsible for execution of re-index process for specific indexer and entity ids
 */
class ReindexOnSaveAction
{
    /**
     * Review feed indexer id
     */
    public const REVIEW_FEED_INDEXER = 'catalog_data_exporter_product_reviews';

    /**
     * Rating metadata feed indexer id
     */
    public const RATING_METADATA_FEED_INDEXER = 'catalog_data_exporter_rating_metadata';

    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(
        IndexerRegistry $indexerRegistry
    ) {
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * Execute re-indexation action
     *
     * @param string $feedIndexer
     * @param int[] $ids
     *
     * @return void
     */
    public function execute(string $feedIndexer, array $ids): void
    {
        $indexer = $this->indexerRegistry->get($feedIndexer);

        if (!$indexer->isScheduled()) {
            $indexer->reindexList($ids);
        }
    }
}
