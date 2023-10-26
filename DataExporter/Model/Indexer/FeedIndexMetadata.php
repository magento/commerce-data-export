<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

/**
 * Feed indexer metadata provider
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class FeedIndexMetadata
{
    public const FEED_TABLE_FIELD_IS_DELETED = 'is_deleted';
    public const FEED_TABLE_FIELD_MODIFIED_AT = 'modified_at';
    public const FEED_TABLE_FIELD_FEED_HASH = 'feed_hash';
    public const FEED_TABLE_FIELD_FEED_DATA = 'feed_data';
    public const FEED_TABLE_FIELD_STATUS = 'status';
    public const FEED_TABLE_FIELD_ERRORS = 'errors';
    /**
     * Default columns that must be updated each time when feed persisted to storage
     */
    private const FEED_TABLE_MUTABLE_COLUMNS_DEFAULT = [
        self::FEED_TABLE_FIELD_FEED_DATA => self::FEED_TABLE_FIELD_FEED_DATA,
        self::FEED_TABLE_FIELD_IS_DELETED => self::FEED_TABLE_FIELD_IS_DELETED,
        self::FEED_TABLE_FIELD_FEED_HASH => self::FEED_TABLE_FIELD_FEED_HASH,
        self::FEED_TABLE_FIELD_STATUS => self::FEED_TABLE_FIELD_STATUS,
        self::FEED_TABLE_FIELD_ERRORS => self::FEED_TABLE_FIELD_ERRORS,
        self::FEED_TABLE_FIELD_MODIFIED_AT => self::FEED_TABLE_FIELD_MODIFIED_AT
    ];

    /**
     * Default feed fields that have to be excluded from hash calculation
     */
    private const EXCLUDE_FROM_HASH_FIELDS_DEFAULT = [
        'modifiedAt',
        'updatedAt'
    ];

    /**
     * @var string
     */
    private $feedName;

    /**
     * @var string
     */
    private $sourceTableName;

    /**
     * @var string
     */
    private $sourceTableField;

    /**
     * @var string
     */
    private $feedIdentity;

    /**
     * @var string
     */
    private $feedTableName;

    /**
     * @var string
     */
    private $feedTableField;

    /**
     * @var string[]
     */
    private $feedTableMutableColumns;

    /**
     * @var int
     */
    private $batchSize;

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
     * @var bool
     */
    private bool $exportImmediately;

    /**
     * @var bool
     */
    private bool $persistExportedFeed;

    /**
     * @var array
     */
    private array $minimalPayload;

    private array $excludeFromHashFields;

    /**
     * @var array
     */
    private array $feedIdentifierMapping;

    /**
     * Mutable field: set during partial indexation
     *
     * @var null|string
     */
    private ?string $modifiedAtTimeInDBFormat;

    /**
     * @param string $feedName
     * @param string $sourceTableName
     * @param string $sourceTableField
     * @param string $feedIdentity
     * @param string $feedTableName
     * @param string $feedTableField
     * @param array $feedTableMutableColumns
     * @param int $batchSize
     * @param int $feedOffsetLimit
     * @param string|null $sourceTableIdentityField
     * @param int $fullReIndexSecondsLimit
     * @param string $sourceTableFieldOnFullReIndexLimit
     * @param bool $truncateFeedOnFullReindex
     * @param array $feedIdentifierMapping
     * @param array $minimalPayload
     * @param array $excludeFromHashFields
     * @param bool $exportImmediately
     * @param bool $persistExportedFeed
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        string $feedName,
        string $sourceTableName,
        string $sourceTableField,
        string $feedIdentity,
        string $feedTableName,
        string $feedTableField,
        array $feedTableMutableColumns = [],
        int $batchSize = 100,
        int $feedOffsetLimit = 100,
        string $sourceTableIdentityField = null,
        int $fullReIndexSecondsLimit = 0,
        string $sourceTableFieldOnFullReIndexLimit = 'updated_at',
        bool $truncateFeedOnFullReindex = true,
        array $feedIdentifierMapping = [],
        array $minimalPayload = [],
        array $excludeFromHashFields = [],
        bool $exportImmediately = false,
        bool $persistExportedFeed = false
    ) {
        $this->feedName = $feedName;
        $this->sourceTableName = $sourceTableName;
        $this->sourceTableField = $sourceTableField;
        $this->feedIdentity = $feedIdentity;
        $this->feedTableName = $feedTableName;
        $this->feedTableField = $feedTableField;
        $this->feedTableMutableColumns = array_unique(array_merge(
            $feedTableMutableColumns,
            self::FEED_TABLE_MUTABLE_COLUMNS_DEFAULT
        ));
        $this->batchSize = $batchSize;
        $this->sourceTableIdentityField = $sourceTableIdentityField ?? $sourceTableField;
        $this->fullReindexSecondsLimit = $fullReIndexSecondsLimit;
        $this->sourceTableFieldOnFullReIndexLimit = $sourceTableFieldOnFullReIndexLimit;
        $this->feedOffsetLimit = $feedOffsetLimit;
        $this->truncateFeedOnFullReindex = $truncateFeedOnFullReindex;
        $this->exportImmediately = $exportImmediately;
        $this->persistExportedFeed = $persistExportedFeed;
        $this->minimalPayload = $minimalPayload;
        $this->excludeFromHashFields = array_unique(array_merge(
            $excludeFromHashFields,
            self::EXCLUDE_FROM_HASH_FIELDS_DEFAULT
        ));

        $this->feedIdentifierMapping = $feedIdentifierMapping;
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
     *
     * @return int the amount in seconds, 0 means no limit
     */
    public function getFullReIndexSecondsLimit(): int
    {
        return $this->fullReindexSecondsLimit;
    }

    /**
     * Table field name to use when full reindex is limited (see fullReindexSecondsLimit)
     *
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

    /**
     * Check is export immediately
     *
     * @return bool
     */
    public function isExportImmediately(): bool
    {
        return $this->exportImmediately;
    }

    /**
     * Check is persist exported feed
     *
     * @return bool
     */
    public function isPersistExportedFeed(): bool
    {
        return $this->persistExportedFeed;
    }

    /**
     * Bare minimum list of fields. Used to send request with deleted entity
     *
     * @return array
     */
    public function getMinimalPayloadFieldsList(): array
    {
        return $this->minimalPayload;
    }

    /**
     * Get feed identifier mapping fields
     *
     * @return array
     */
    public function getFeedIdentifierMappingFields(): array
    {
        return $this->feedIdentifierMapping;
    }

    /**
     * Get exclude from hash fields
     *
     * @return array
     */
    public function getExcludeFromHashFields(): array
    {
        return $this->excludeFromHashFields;
    }

    /**
     * @return string|null
     */
    public function getCurrentModifiedAtTimeInDBFormat(): ?string
    {
        return $this->modifiedAtTimeInDBFormat;
    }

    /**
     * @param string $modifiedAtTimeInDBFormat
     */
    public function setCurrentModifiedAtTimeInDBFormat(string $modifiedAtTimeInDBFormat): void
    {
        $this->modifiedAtTimeInDBFormat = $modifiedAtTimeInDBFormat;
    }
}
