<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

class IndexStateProvider
{
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
    private array $processedHashes = [];

    /**
     * @param array $feedItems
     * @return void
     */
    public function addItems(array $feedItems): void
    {
        $this->feedItems += $feedItems;
    }

    /**
     * Warming: this function is NOT idempotent
     * @return array
     */
    public function getFeedItems(): array
    {
        $feedItems = [];
        while ($item = array_shift($this->feedItems)) {
            $feedItems[] = $item;
            if (count($feedItems) === $this->batchSize) {
                break;
            }
        }
        return $feedItems;
    }

    /**
     * @return bool
     */
    public function isBatchLimitReached(): bool
    {
        return count($this->feedItems) >= $this->batchSize;
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
}
