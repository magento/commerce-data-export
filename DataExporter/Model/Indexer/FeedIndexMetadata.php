<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

/**
 * Feed indexer metadata provider
 */
class FeedIndexMetadata
{
    /**
     * @var string
     */
    protected $feedName;

    /**
     * @var string
     */
    protected $sourceTableName;

    /**
     * @var string
     */
    protected $sourceTableField;

    /**
     * @var string
     */
    protected $feedIdentity;

    /**
     * @var string
     */
    protected $feedTableName;

    /**
     * @var string
     */
    protected $feedTableField;

    /**
     * @var string[]
     */
    protected $feedTableMutableColumns;

    /**
     * @var int
     */
    protected $batchSize;

    /**
     * Field used in WHERE & ORDER statement during select IDs from source table
     *
     * @var string
     */
    private $sourceTableIdentityField;

    /**
     * @var int
     */
    private $fullReindexSecondsLimit;

    /**
     * @var string
     */
    private $sourceTableFieldOnFullReIndexLimit;

    /**
     * @var int
     */
    private int $feedOffsetLimit;

    /**
     * @var bool
     */
    private bool $truncateFeedOnFullReindex;

    /**
     * @param string $feedName
     * @param string $sourceTableName
     * @param string $sourceTableField
     * @param string $feedIdentity
     * @param string $feedTableName
     * @param string $feedTableField
     * @param array $feedTableMutableColumns
     * @param int $batchSize
     * @param string|null $sourceTableIdentityField
     * @param int $fullReIndexSecondsLimit
     * @param string $sourceTableFieldOnFullReIndexLimit
     */
    public function __construct(
        string $feedName,
        string $sourceTableName,
        string $sourceTableField,
        string $feedIdentity,
        string $feedTableName,
        string $feedTableField,
        array $feedTableMutableColumns,
        int $batchSize = 100,
        int $feedOffsetLimit = 100,
        string $sourceTableIdentityField = null,
        int $fullReIndexSecondsLimit = 0,
        string $sourceTableFieldOnFullReIndexLimit = 'updated_at',
        bool $truncateFeedOnFullReindex = true
    ) {
        $this->feedName = $feedName;
        $this->sourceTableName = $sourceTableName;
        $this->sourceTableField = $sourceTableField;
        $this->feedIdentity = $feedIdentity;
        $this->feedTableName = $feedTableName;
        $this->feedTableField = $feedTableField;
        $this->feedTableMutableColumns = $feedTableMutableColumns;
        $this->batchSize = $batchSize;
        $this->sourceTableIdentityField = $sourceTableIdentityField ?? $sourceTableField;
        $this->fullReindexSecondsLimit = $fullReIndexSecondsLimit;
        $this->sourceTableFieldOnFullReIndexLimit = $sourceTableFieldOnFullReIndexLimit;
        $this->feedOffsetLimit = $feedOffsetLimit;
        $this->truncateFeedOnFullReindex = $truncateFeedOnFullReindex;
    }

    /**
     * Get Feed Name
     *
     * @return string
     */
    public function getFeedName(): string
    {
        return $this->feedName;
    }

    /**
     * Get source table name
     *
     * @return string
     */
    public function getSourceTableName(): string
    {
        return $this->sourceTableName;
    }

    /**
     * Get source table field
     *
     * @return string
     */
    public function getSourceTableField(): string
    {
        return $this->sourceTableField;
    }

    /**
     * Get source table identity field
     *
     * @return string
     */
    public function getSourceTableIdentityField(): string
    {
        return $this->sourceTableIdentityField;
    }

    /**
     * Feed identity. Used as argument name in Data Provider that holds ids from Source Table
     *
     * @return string
     */
    public function getFeedIdentity(): string
    {
        return $this->feedIdentity;
    }

    /**
     * Get feed table name
     *
     * @return string
     */
    public function getFeedTableName(): string
    {
        return $this->feedTableName;
    }

    /**
     * Feed Table Field - part of the feed identity, used to find all feed items and mark entity as "deleted"
     *
     * @return string
     */
    public function getFeedTableField(): string
    {
        return $this->feedTableField;
    }

    /**
     * Get batch size
     *
     * @return int
     */
    public function getBatchSize(): int
    {
        return $this->batchSize;
    }

    /**
     * Get feed table mutable column names
     *
     * @return string[]
     */
    public function getFeedTableMutableColumns(): array
    {
        return $this->feedTableMutableColumns;
    }

    /**
     * Determines the amount of seconds back in time when triggering a full reindex
     * @return int the amount in seconds, 0 means no limit
     */
    public function getFullReIndexSecondsLimit(): int
    {
        return $this->fullReindexSecondsLimit;
    }

    /**
     * Table field name to use when full reindex is limited (see fullReindexSecondsLimit)
     * @return string the field name
     */
    public function getSourceTableFieldOnFullReIndexLimit(): string
    {
        return $this->sourceTableFieldOnFullReIndexLimit;
    }

    /**
     * Limit number of items returned in query
     *
     * @return int
     */
    public function getFeedOffsetLimit(): int
    {
        return $this->feedOffsetLimit;
    }

    /**
     * Determine if need to truncate feed indexer during full reindex
     *
     * @return bool
     */
    public function isTruncateFeedOnFullReindex(): bool
    {
        return $this->truncateFeedOnFullReindex;
    }
}
