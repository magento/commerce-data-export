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

namespace Magento\DataExporter\Model\Indexer;

class IndexStateProvider
{
    public const INSERT_OPERATION = 1;
    public const UPDATE_OPERATION = 2;

    /**
     * @param int $batchSize
     */
    public function __construct(int $batchSize)
    {
        $this->batchSize = $batchSize;
    }

    /**
     * @var int
     */
    private int $batchSize;

    /**
     * @var array
     */
    private array $feedItems = [];

    /**
     * @var array
     */
    private array $feedItemsUpdates = [];

    /**
     * @var array
     */
    private array $processedHashes = [];

    /**
     * Add new feed items suitable for insert operation
     *
     * @param array $feedItems
     * @return void
     */
    public function addItems(array $feedItems): void
    {
        $this->feedItems = array_merge($this->feedItems, $feedItems);
    }

    /**
     * Add updated feed items suitable for update operation
     *
     * @param array $feedItems
     * @return void
     */
    public function addUpdates(array $feedItems): void
    {
        $this->feedItemsUpdates = array_merge($this->feedItemsUpdates, $feedItems);
    }

    /**
     * Warming: this function is NOT idempotent
     *
     * @return array
     */
    public function getFeedItems(): array
    {
        $feedItems = [];
        $batchLimitReached = false;
        while ($item = array_shift($this->feedItems)) {
            $item['operation'] = self::INSERT_OPERATION;
            $feedItems[] = $item;
            if (count($feedItems) === $this->batchSize) {
                $batchLimitReached = true;
                break;
            }
        }
        if (!$batchLimitReached) {
            while ($item = array_shift($this->feedItemsUpdates)) {
                $item['operation'] = self::UPDATE_OPERATION;
                $feedItems[] = $item;
                if (count($feedItems) === $this->feedItemsUpdates) {
                    break;
                }
            }
        }
        return $feedItems;
    }

    /**
     * @return bool
     */
    public function isBatchLimitReached(): bool
    {
        $batchSize = count($this->feedItems) + count($this->feedItemsUpdates);
        return $batchSize >= $this->batchSize;
    }

    /**
     * @return array
     */
    public function getProcessedHashes(): array
    {
        $processedHashes = $this->processedHashes;
        $this->processedHashes = [];
        return $processedHashes;
    }

    /**
     * @param string $hash
     * @return void
     */
    public function addProcessedHash(string $hash): void
    {
        $this->processedHashes[$hash] = true;
    }

    /**
     * Check operation type
     *
     * @param array $item
     * @return bool
     */
    public static function isUpdate(array $item): bool
    {
        return $item['operation'] === self::UPDATE_OPERATION;
    }
}
