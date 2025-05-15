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
     * Get failed items
     *
     * @return array
     */
    public function getFailed(): array
    {
        $return = $this->feedItemsFailed ?? [];
        $this->feedItemsFailed = [];
        return $return;
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
}
