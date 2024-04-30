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

namespace Magento\DataExporter\Model\Batch\Feed;

use Magento\DataExporter\Model\Batch\BatchIteratorInterface;
use Magento\DataExporter\Model\Batch\BatchTable;
use Magento\DataExporter\Model\Batch\BatchLocator;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\App\ResourceConnection;

/**
 * Batch iterator based on items from feed table.
 */
class Iterator implements BatchIteratorInterface
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var BatchLocator
     */
    private BatchLocator $batchLocator;

    /**
     * @var BatchTable
     */
    private BatchTable $batchTable;

    /**
     * @var string
     */
    private string $sourceTableName;

    /**
     * @var array
     */
    private array $sourceTableKeyColumns;

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
     * Is removable flag.
     *
     * @var bool|null
     */
    private ?bool $isRemovable;

    /**
     * @param ResourceConnection $resourceConnection
     * @param BatchLocator $batchLocator
     * @param BatchTable $batchTable
     * @param string $sourceTableName
     * @param array $sourceTableKeyColumns
     * @param bool|null $isRemovable
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        BatchLocator       $batchLocator,
        BatchTable         $batchTable,
        string             $sourceTableName,
        array              $sourceTableKeyColumns,
        ?bool              $isRemovable = null
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->batchLocator = $batchLocator;
        $this->batchTable = $batchTable;
        $this->sourceTableName = $sourceTableName;
        $this->sourceTableKeyColumns = $sourceTableKeyColumns;
        $this->isRemovable = $isRemovable;
    }

    /**
     * @inheritDoc
     */
    public function current(): array
    {
        if (null === $this->currentBatch) {
            $this->currentBatch = $this->getBatch();
            $this->isValid = count($this->currentBatch['feed']) > 0;
        }

        return $this->currentBatch;
    }

    /**
     * @inheritDoc
     */
    public function next(): void
    {
        $batch = $this->getBatch();
        $this->isValid = count($batch['feed']) > 0;
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
     * Returns batch data.
     *
     * @return array
     * @throws \Zend_Db_Statement_Exception
     * @throws \Exception
     */
    private function getBatch(): array
    {
        $connection = $this->resourceConnection->getConnection();
        $this->batchNumber = $this->batchLocator->getNumber();
        $pkJoinConditions = array_map(
            function ($key) {
                return sprintf('t.%s = b.%s', $key, $key);
            },
            $this->sourceTableKeyColumns
        );

        $select = $connection->select()
            ->from(
                ['t' => $this->sourceTableName],
                [
                    't.' . FeedIndexMetadata::FEED_TABLE_FIELD_FEED_DATA,
                    't.' . FeedIndexMetadata::FEED_TABLE_FIELD_MODIFIED_AT
                ]
            )
            ->join(
                ['b' => $this->batchTable->getBatchTableName()],
                implode(' AND ', $pkJoinConditions),
                []
            )
            ->where($this->batchTable->getBatchNumberField() . ' = ?', $this->batchNumber);
        if (true === $this->isRemovable) {
            $select->columns(
                't.' . FeedIndexMetadata::FEED_TABLE_FIELD_IS_DELETED
            );
        }
        $statement = $connection->query($select);
        $data = [];
        while ($row = $statement->fetch()) {
            $entity = json_decode($row[FeedIndexMetadata::FEED_TABLE_FIELD_FEED_DATA], true);
            $entity['deleted'] = $this->isRemovable && $row[FeedIndexMetadata::FEED_TABLE_FIELD_IS_DELETED];

            $data[] = $entity;

        }

        return [
            'feed' => $data,
        ];
    }

    /**
     * @inheritDoc
     */
    public function markBatchForRetry(): void
    {
        $connection = $this->resourceConnection->getConnection();
        $pkJoinConditions = array_map(
            function ($key) {
                return sprintf('t.%s = b.%s', $key, $key);
            },
            $this->sourceTableKeyColumns
        );

        $query = sprintf(
            "UPDATE %s as t INNER JOIN %s as b ON %s
                      SET t.`modified_at` = CURRENT_TIMESTAMP()
                      WHERE b.%s = %d",
            $this->sourceTableName,
            $this->batchTable->getBatchTableName(),
            implode(' AND ', $pkJoinConditions),
            $this->batchTable->getBatchNumberField(),
            $this->batchNumber
        );
        $connection->query($query);
    }
}
