<?php
/*************************************************************************
 *
 * Copyright 2023 Adobe
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
 * ***********************************************************************
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Batch\FeedSource;

use Magento\DataExporter\Model\Batch\BatchIteratorInterface;
use Magento\DataExporter\Model\Batch\BatchTable;
use Magento\DataExporter\Model\Batch\BatchLocator;
use Magento\Framework\App\ResourceConnection;

/**
 * Batch iterator based on items from feed source table.
 */
class Iterator implements BatchIteratorInterface
{
    /**
     * @var BatchTable
     */
    private BatchTable $batchTable;

    /**
     * @var string
     */
    private string $sourceTableKeyColumn;

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var BatchLocator
     */
    private BatchLocator $batchLocator;

    /**
     * @var array|null
     */
    private ?array $currentBatch = null;

    /**
     * @var bool
     */
    private bool $isValid;

    /**
     * @var int
     */
    private int $batchNumber = 0;

    /**
     * @var int|null
     */
    private ?int $count = null;

    /**
     * @param ResourceConnection $resourceConnection
     * @param BatchLocator $batchLocator
     * @param BatchTable $batchTable
     * @param string $sourceTableKeyColumn
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        BatchLocator       $batchLocator,
        BatchTable         $batchTable,
        string             $sourceTableKeyColumn,
    ) {
        $this->batchTable = $batchTable;
        $this->sourceTableKeyColumn = $sourceTableKeyColumn;
        $this->resourceConnection = $resourceConnection;
        $this->batchLocator = $batchLocator;
    }

    /**
     * @inheritDoc
     */
    public function current(): array
    {
        if (null === $this->currentBatch) {
            $this->currentBatch = $this->getBatch();
            $this->isValid = count($this->currentBatch) > 0;
        }

        return $this->currentBatch;
    }

    /**
     * @inheritDoc
     */
    public function next(): void
    {
        $batch = $this->getBatch();
        $this->isValid = count($batch) > 0;
        if ($this->isValid) {
            $this->currentBatch = $batch;
        } else {
            $this->currentBatch = null;
        }
    }

    /**
     * @inheritDoc
     */
    public function key(): mixed
    {
        return $this->batchNumber;
    }

    /**
     * @inheritDoc
     */
    public function valid(): bool
    {
        return $this->isValid;
    }

    /**
     * @inheritDoc
     */
    public function rewind(): void
    {
        $this->currentBatch = null;
        $this->isValid = true;
        $this->batchNumber = 0;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        if ($this->count === null) {
            $this->count = $this->batchTable->getBatchCount();
        }

        return $this->count;
    }

    /**
     * Returns batch.
     *
     * @return array
     */
    private function getBatch(): array
    {
        $connection = $this->resourceConnection->getConnection();
        $this->batchNumber = $this->batchLocator->getNumber();
        $select = $connection->select()
            ->from(
                ['t' => $this->batchTable->getBatchTableName()],
                [$this->sourceTableKeyColumn]
            )
            ->where($this->batchTable->getBatchNumberField() . ' = ?', $this->batchNumber);

        return $connection->fetchCol($select);
    }

    /**
     * @inheritDoc
     * phpcs:disable Magento2.CodeAnalysis.EmptyBlock
     */
    public function markBatchForRetry(): void
    {
        // Is not needed for this iterator.
    }
}
