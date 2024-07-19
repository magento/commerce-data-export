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

use Magento\Framework\App\ObjectManager;

/**
 * Feed indexer metadata provider
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class FeedIndexMetadata
{
    public const FEED_TABLE_FIELD_PK = 'id';
    public const FEED_TABLE_FIELD_SOURCE_ENTITY_ID = 'source_entity_id';
    public const FEED_TABLE_FIELD_IS_DELETED = 'is_deleted';
    public const FEED_TABLE_FIELD_MODIFIED_AT = 'modified_at';
    public const FEED_TABLE_FIELD_FEED_ID = 'feed_id';
    public const FEED_TABLE_FIELD_FEED_HASH = 'feed_hash';
    public const FEED_TABLE_FIELD_FEED_DATA = 'feed_data';
    public const FEED_TABLE_FIELD_STATUS = 'status';
    public const FEED_TABLE_FIELD_ERRORS = 'errors';
    /**
     * Default columns that must be updated each time when feed persisted to storage
     */
    private const FEED_TABLE_MUTABLE_COLUMNS_DEFAULT = [
        self::FEED_TABLE_FIELD_SOURCE_ENTITY_ID => self::FEED_TABLE_FIELD_SOURCE_ENTITY_ID,
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
    private const DB_DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * @var string
     */
    private $feedName;

    /**
     * @var string
     */
    private $feedSummary;
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

    /**
     * @var array
     */
    private array $excludeFromHashFields;

    /**
     * @var array
     */
    private array $feedIdentifierMapping;

    /**
     * @var bool
     */
    private bool $entitiesRemovable;

    /**
     * @var Config|null
     */
    private ?Config $config;

    /**
     * @var string|null
     */
    private ?string $dateTimeFormat;

    /**
     * @var string|null
     */
    private ?string $viewSourceLinkField;

    /**
     * @var array
     */
    private array $feedItemIdentifiers;

    /**
     * @param string $feedName
     * @param string $sourceTableName
     * @param string $sourceTableField
     * @param string $feedIdentity
     * @param string $feedTableName
     * @param string $feedTableField
     * @param array $feedTableMutableColumns
     * @param string|null $sourceTableIdentityField
     * @param int $fullReIndexSecondsLimit
     * @param string $sourceTableFieldOnFullReIndexLimit
     * @param bool $truncateFeedOnFullReindex
     * @param array $feedIdentifierMapping
     * @param array $feedItemIdentifiers
     * @param array $minimalPayload
     * @param array $excludeFromHashFields
     * @param bool $exportImmediately
     * @param bool $persistExportedFeed
     * @param bool $entitiesRemovable
     * @param string|null $dateTimeFormat
     * @param Config|null $config
     * @param string|null $feedSummary
     * @param string|null $viewSourceLinkField
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        string $feedName,
        string $sourceTableName,
        string $sourceTableField,
        string $feedIdentity,
        string $feedTableName,
        string $feedTableField = self::FEED_TABLE_FIELD_SOURCE_ENTITY_ID,
        array $feedTableMutableColumns = [],
        string $sourceTableIdentityField = null,
        int $fullReIndexSecondsLimit = 0,
        string $sourceTableFieldOnFullReIndexLimit = 'updated_at',
        bool $truncateFeedOnFullReindex = true,
        array $feedIdentifierMapping = [],
        array $feedItemIdentifiers = [],
        array $minimalPayload = [],
        array $excludeFromHashFields = [],
        bool $exportImmediately = false,
        bool $persistExportedFeed = false,
        bool $entitiesRemovable = false,
        ?string $dateTimeFormat = null,
        ?Config $config = null,
        ?string $feedSummary = null,
        ?string $viewSourceLinkField = null
    ) {
        $this->feedName = $feedName;
        $this->feedSummary = $feedSummary ?? '';
        $this->sourceTableName = $sourceTableName;
        $this->sourceTableField = $sourceTableField;
        $this->feedIdentity = $feedIdentity;
        $this->feedTableName = $feedTableName;
        $this->feedTableField = $feedTableField;
        $this->feedTableMutableColumns = array_unique(array_merge(
            $feedTableMutableColumns,
            self::FEED_TABLE_MUTABLE_COLUMNS_DEFAULT
        ));
        $this->sourceTableIdentityField = $sourceTableIdentityField ?? $sourceTableField;
        $this->fullReindexSecondsLimit = $fullReIndexSecondsLimit;
        $this->sourceTableFieldOnFullReIndexLimit = $sourceTableFieldOnFullReIndexLimit;
        $this->truncateFeedOnFullReindex = $truncateFeedOnFullReindex;
        $this->exportImmediately = $exportImmediately;
        $this->persistExportedFeed = $persistExportedFeed;
        $this->minimalPayload = $minimalPayload;
        $this->excludeFromHashFields = array_unique(array_merge(
            $excludeFromHashFields,
            self::EXCLUDE_FROM_HASH_FIELDS_DEFAULT
        ));

        $this->feedIdentifierMapping = $feedIdentifierMapping;
        $this->dateTimeFormat = $dateTimeFormat;
        $this->entitiesRemovable = $entitiesRemovable;
        $this->viewSourceLinkField = $viewSourceLinkField;
        $this->config = $config ?? ObjectManager::getInstance()->get(Config::class);
        $this->feedItemIdentifiers = $feedItemIdentifiers;
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
     * Get Feed Summary
     *
     * @return string
     */
    public function getFeedSummary(): string
    {
        return $this->feedSummary;
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
        return $this->config->getBatchSize($this->feedName);
    }

    /**
     * Get thread count. Used to run feed indexation and sync in parallel threads.
     *
     * @return int
     */
    public function getThreadCount(): int
    {
        return $this->config->getThreadCount($this->feedName);
    }

    /**
     * Whether resync  process should be continued from the last position
     */
    public function isResyncShouldBeContinued(): bool
    {
        return $this->config->isResyncShouldBeContinued();
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
     * @deprecated
     * @see getBatchSize
     */
    public function getFeedOffsetLimit(): int
    {
        return $this->getBatchSize();
    }

    /**
     * Determine if you need to truncate feed indexer during full reindex
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
        return array_merge(
            $this->minimalPayload,
            // Feed identity is a required field to build feed item
            [$this->getFeedIdentity() => $this->getFeedIdentity()]
        );
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
     * Get feed item identifiers fields
     *
     * @return array
     */
    public function getFeedItemIdentifiers(): array
    {
        return $this->feedItemIdentifiers;
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
     * Check if feed metadata has flag that entities are removable
     *
     * @return bool
     */
    public function isRemovable(): bool
    {
        return $this->entitiesRemovable;
    }

    /**
     * Get date time format for feed data
     *
     * @return string|null
     */
    public function getDateTimeFormat(): ?string
    {
        return $this->dateTimeFormat ?? $this->getDbDateTimeFormat();
    }

    /**
     * Get default date time format
     *
     * @return string
     */
    public function getDbDateTimeFormat(): string
    {
        return self::DB_DATE_TIME_FORMAT;
    }

    /**
     * Get view -> source link field for mapping with source table field
     *
     * Use when view entity field is different from source table entity field.
     * It will use this field to link view identity field with source identity field.
     *
     * @return string|null
     */
    public function getViewSourceLinkField(): ?string
    {
        return $this->viewSourceLinkField;
    }
}
