<?php
/**
 * ADOBE CONFIDENTIAL
 * ___________________
 *
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

namespace Magento\DataExporterStatus\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\DataExporter\Model\FeedInterface;
use Magento\DataExporter\Model\FeedPool;
use Magento\DataExporterStatus\Service\DTO\IndexerStatus;
use Magento\DataExporterStatus\Service\IndexerStatusProvider;
use Magento\DataExporterStatus\Ui\DataProvider\FeedOptionsDataProvider;
use Magento\Framework\Indexer\StateInterface;

class FeedStatusInfo extends Section
{
    private ?string $feedName = null;
    private ?FeedInterface $feed = null;
    private ?\Magento\DataExporterStatus\Service\DTO\IndexerStatus $indexerStatus = null;

    public function __construct(
        Context $context,
        private readonly FeedPool $feedPool,
        private readonly IndexerStatusProvider $indexerStatusProvider,
        private readonly FeedOptionsDataProvider $feedOptionsDataProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get current feed name
     */
    public function getCurrentFeedName(): string
    {
        if ($this->feedName === null) {
            $this->feedName = '';
            
            try {
                $request = $this->getRequest();
                if ($request) {
                    $this->feedName = (string) $request->getParam('feed', '');
                    
                    // Also check filters parameter
                    $filters = $request->getParam('filters');
                    if ($filters && is_array($filters) && isset($filters['feed'])) {
                        $this->feedName = (string) $filters['feed'];
                    }
                }
            } catch (\Exception $e) {
                $this->_logger->error('Error getting feed name from request: ' . $e->getMessage());
                $this->feedName = '';
            }
        }
        
        return $this->feedName;
    }

    /**
     * Get current feed object
     */
    public function getCurrentFeed(): ?FeedInterface
    {
        if ($this->feed === null) {
            try {
                $this->feed = $this->feedPool->getFeed($this->getCurrentFeedName());
            } catch (\Exception $e) {
                // If feed not found, get the first available feed
                $feeds = $this->feedPool->getList();
                if (!empty($feeds)) {
                    $this->feed = array_values($feeds)[0];
                    $this->feedName = $this->feed->getFeedMetadata()->getFeedName();
                }
            }
        }
        
        return $this->feed;
    }

    /**
     * Get indexer status information
     */
    public function getIndexerStatus(): ?\Magento\DataExporterStatus\Service\DTO\IndexerStatus
    {
        if ($this->indexerStatus === null) {
            $feed = $this->getCurrentFeed();
            if ($feed) {
                try {
                    $this->indexerStatus = $this->indexerStatusProvider->getIndexerStatus(
                        $feed->getFeedMetadata()
                    );
                } catch (\Exception $e) {
                    // Log error but don't break the page
                    $this->_logger->error('Error getting indexer status: ' . $e->getMessage());
                }
            }
        }
        
        return $this->indexerStatus;
    }

    public function isScheduleModeEnabled(): bool
    {
        return $this->getIndexerStatus()->getIndexer()->isScheduled();
    }
    /**
     * Get formatted indexer status text
     */
    public function getFormattedIndexerStatus(): string
    {
        $indexerStatus = $this->getIndexerStatus();
        if (!$indexerStatus) {
            return (string) __('Status unavailable');
        }

        return $this->getIndexerStatusText($indexerStatus);
    }

    /**
     * Get human-readable indexer status text
     */
    private function getIndexerStatusText(IndexerStatus $status): string
    {
        $indexer = $status->getIndexer();
        return match ($indexer->getStatus()) {
            // TODO: add timestmap from indexer_state table
            StateInterface::STATUS_VALID => (string) __('Ready'),
            StateInterface::STATUS_INVALID => (string) __('Reindex required'),
            StateInterface::STATUS_WORKING => (string) __('Processing'),
            StateInterface::STATUS_SUSPENDED => (string) __('Suspended'),
            default => $indexer->getStatus()
        };
    }

    /**
     * Check if indexer is in progress
     */
    public function isIndexerInProgress(): bool
    {
        $status = $this->getIndexerStatus();
        return $status && $status->getIndexer()->getStatus() === StateInterface::STATUS_WORKING;
    }

    /**
     * Check if indexer is invalid
     */
    public function isIndexerInvalid(): bool
    {
        $status = $this->getIndexerStatus();
        return $status && $status->getIndexer()->getStatus() === StateInterface::STATUS_INVALID;
    }

    public function getChangelogBacklogCount(): int
    {
        $status = $this->getIndexerStatus();
        return $status ? $status->getChangelogBacklog() : 0;
    }

    public function getChangelogLastUpdated(): string
    {
        $status = $this->getIndexerStatus();
        return $status && $status->getChangelogLastUpdated() !== null ? $status->getChangelogLastUpdated() : 'N/A';
    }

    /**
     * Get feed options for selectbox
     */
    public function getFeedOptions(): array
    {
        return $this->feedOptionsDataProvider->toOptionArray();
    }
}
