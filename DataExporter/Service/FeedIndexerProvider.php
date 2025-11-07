<?php
/**
 * Copyright 2025 Adobe
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

namespace Magento\DataExporter\Service;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerRegistry;

/**
 * Return Feed Indexer instance for given metadata
 */
class FeedIndexerProvider
{
    /**
     * Constructor
     *
     * @param IndexerRegistry $indexerRegistry
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        private readonly IndexerRegistry $indexerRegistry,
        private readonly CommerceDataExportLoggerInterface $logger
    ) {
    }

    /**
     * Get indexer by feed metadata
     *
     * @param FeedIndexMetadata $metadata
     * @return IndexerInterface|null
     */
    public function getIndexer(FeedIndexMetadata $metadata): ?IndexerInterface
    {
        if ($metadata->getIndexerId() == null) {
            $this->logger->warning(
                'Feed metadata does not contain indexer name',
                ['feedName' => $metadata->getFeedName()]
            );
            return null;
        }

        try {
            return $this->indexerRegistry->get($metadata->getIndexerId());
        } catch (\Exception $e) {
            $this->logger->error(
                'Cannot load feed indexer for feed',
                ['feedName' => $metadata->getFeedName(), 'error' => $e->getMessage()]
            );
        }
        return null;
    }
}
