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

namespace Magento\DataExporter\Model\Batch\FeedChangeLog;

use Magento\DataExporter\Model\Batch\BatchIteratorInterface;
use Magento\DataExporter\Model\Batch\BatchTable;
use Magento\DataExporter\Model\Batch\BatchLocator;
use Magento\Framework\App\ResourceConnection;

/**
 * Batch iterator based on items from feed change log table.
 */
class Iterator implements BatchIteratorInterface
{
    /**
     * @var BatchTable
     */
    private BatchTable $batchTable;

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
    private int $batchNum = 0;

    /**
     * @var int|null
     */
    private ?int $count = null;

    /**
     * @var string
     */
    private string $sourceTableName;

    /**
     * @var string
     */
    private string $sourceTableKeyColumn;

    /**
     * @param ResourceConnection $resourceConnection
     * @param BatchLocator $batchLocator
     * @param BatchTable $batchTable
     * @param string $sourceTableName
     * @param string $sourceTableKeyColumn
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        BatchLocator       $batchLocator,
        BatchTable         $batchTable,
        string             $sourceTableName,
        string             $sourceTableKeyColumn
    ) {

        $this->batchTable = $batchTable;
        $this->resourceConnection = $resourceConnection;
        $this->batchLocator = $batchLocator;
        $this->sourceTableName = $sourceTableName;
        $this->sourceTableKeyColumn = $sourceTableKeyColumn;
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
        return $this->batchNum;
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
        $this->batchNum = 0;
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
     * Get batch of items from feed change log table.
     *
     * @return array
     */
    private function getBatch(): array
    {
        $connection = $this->resourceConnection->getConnection();
        $this->batchNum = $this->batchLocator->getNumber();
        $select = $connection->select()
            ->from(
                ['t' => $this->batchTable->getBatchTableName()],
                [$this->sourceTableKeyColumn]
            )
            ->where($this->batchTable->getBatchNumberField() . ' = ?', $this->batchNum);

        return $connection->fetchCol($select);
    }

    /**
     * @inheritDoc
     */
    public function markBatchForRetry(): void
    {
        $connection = $this->resourceConnection->getConnection();
        $data = array_map(
            function ($id) {
                return [$this->sourceTableKeyColumn => $id];
            },
            $this->current()
        );
        $connection->insertMultiple($this->sourceTableName, $data);
    }
}
