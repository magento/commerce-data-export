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

use Magento\DataExporter\Model\Indexer\FeedIndexer;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Mview\ActionFactory;
use Magento\Indexer\Model\Indexer\Collection;

/**
 * Return Feed Indexer instance for given metadata
 */
class FeedIndexerProvider
{
    /**
     * @var array|null
     */
    private ?array $feedNameToIndexerMap = null;

    /**
     * Constructor
     *
     * @param Collection $indexerCollection
     * @param ActionFactory $actionFactory
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        private readonly Collection $indexerCollection,
        private readonly ActionFactory $actionFactory,
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
        if ($this->feedNameToIndexerMap === null) {
            foreach ($this->indexerCollection->getItems() as $indexer) {
                try {
                    $actionIndexer = $this->actionFactory->get(
                        $indexer->getActionClass()
                    );
                    if ($actionIndexer instanceof FeedIndexer) {
                        $this->feedNameToIndexerMap[$actionIndexer->getFeedIndexMetadata()->getFeedName()] = $indexer;
                    }
                } catch (\Exception $e) {
                    $this->logger->error(
                        'Cannot load feed indexer',
                        ['indexer' => $indexer->getId(), 'error' => $e->getMessage()]
                    );
                }
            }
        }
        return $this->feedNameToIndexerMap[$metadata->getFeedName()] ?? null;
    }
}
