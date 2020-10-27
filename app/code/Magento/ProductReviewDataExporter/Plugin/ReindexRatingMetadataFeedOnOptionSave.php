<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ProductReviewDataExporter\Plugin;

use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Review\Model\Rating\Option;

/**
 * Plugin for running rating metadata feed indexation after rating option save operation
 */
class ReindexRatingMetadataFeedOnOptionSave
{
    /**
     * Rating metadata feed indexer id
     */
    private const RATING_METADATA_FEED_INDEXER = 'catalog_data_exporter_rating_metadata';

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
     * Execute reindex process on save commit callback
     *
     * @param Option $subject
     *
     * @return Option
     */
    public function beforeAfterCommitCallback(Option $subject): Option
    {
        $indexer = $this->indexerRegistry->get(self::RATING_METADATA_FEED_INDEXER);
        if (!$indexer->isScheduled()) {
            $indexer->reindexRow($subject->getRatingId());
        }

        return $subject;
    }
}
