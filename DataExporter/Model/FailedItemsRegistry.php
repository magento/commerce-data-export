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

namespace Magento\DataExporter\Model;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;

/**
 * Tracks and stores failed feeds
 */
class FailedItemsRegistry
{
    private array $feedItemsFailed = [];

    /**
     * Add failed item to the registry
     *
     * @param array $feedItem
     * @param \Throwable $fail
     */
    public function addFailed(array $feedItem, \Throwable $fail): void
    {
        $feedItem['errors'] = $fail->getMessage() . ' in ' . $fail->getFile() . ':' . $fail->getLine();
        $this->feedItemsFailed = array_merge($this->feedItemsFailed, [$feedItem]);
    }

    /**
     * Add failed items data to the resulting feed
     *
     * @param array $feedItems
     * @param FeedIndexMetadata $feedIndexMetadata
     * @return array
     */
    public function mergeFailedItemsWithFeed(array $feedItems, FeedIndexMetadata $feedIndexMetadata): array
    {
        $failedItems = $this->getStoredFailedItemsAndReset();
        if (empty($failedItems)) {
            return $feedItems;
        }
        $identifierFields = $feedIndexMetadata->getFeedItemIdentifiers();
        $mappedFailures = [];
        foreach ($failedItems as $failedItem) {
            $key = $this->buildKey($failedItem, $identifierFields);
            $mappedFailures[$key] = $failedItem;
        }
        // Merge with existing feed items
        foreach ($feedItems as &$feedItem) {
            $key = $this->buildKey($feedItem, $identifierFields);
            if (isset($mappedFailures[$key])) {
                $feedItem['errors'] = $mappedFailures[$key]['errors'] ?? 'NO ERROR MESSAGE AVAILABLE';
                unset($mappedFailures[$key]);
            }
        }
        // Add remaining failed items to the feed and return result
        return array_merge($feedItems, $mappedFailures);
    }

    /**
     * @param array $feedItem
     * @param array $identifierFields
     * @return string
     */
    private function buildKey(array $feedItem, array $identifierFields): string
    {
        return implode('_', array_map(fn($field) => $feedItem[$field] ?? '', $identifierFields));
    }

    /**
     * Clear the failed items registry
     *
     * @return array
     */
    public function clear(): void
    {
        $this->feedItemsFailed = [];
    }

    public function getStoredFailedItemsAndReset(): array
    {
        $return = $this->feedItemsFailed ?? [];
        $this->clear();

        return $return;
    }
}
