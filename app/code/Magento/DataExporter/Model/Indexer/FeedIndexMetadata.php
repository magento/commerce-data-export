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
     * @var string
     */
    private $scopeTableName;

    /**
     * @var string
     */
    private $scopeTableField;

    /**
     * @var string
     */
    private $scopeCode;

    /**
     * @param string $feedName
     * @param string $sourceTableName
     * @param string $sourceTableField
     * @param string $feedIdentity
     * @param string $feedTableName
     * @param string $feedTableField
     * @param array $feedTableMutableColumns
     * @param string $scopeTableName
     * @param string $scopeTableField
     * @param string $scopeCode
     * @param int $batchSize
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
        array $feedTableMutableColumns,
        string $scopeTableName = '',
        string $scopeTableField = '',
        string $scopeCode = '',
        int $batchSize = 100
    ) {
        $this->feedName = $feedName;
        $this->sourceTableName = $sourceTableName;
        $this->sourceTableField = $sourceTableField;
        $this->feedIdentity = $feedIdentity;
        $this->feedTableName = $feedTableName;
        $this->feedTableField = $feedTableField;
        $this->feedTableMutableColumns = $feedTableMutableColumns;
        $this->scopeTableName = $scopeTableName;
        $this->scopeTableField = $scopeTableField;
        $this->scopeCode = $scopeCode;
        $this->batchSize = $batchSize;
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
     * Get feed identity
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
     * Get feed table field
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
     * Get scope table name
     *
     * @return string
     */
    public function getScopeTableName(): string
    {
        return $this->scopeTableName;
    }

    /**
     * Get scope table field
     *
     * @return string
     */
    public function getScopeTableField(): string
    {
        return $this->scopeTableField;
    }

    /**
     * Get scope table scope code
     *
     * @return string
     */
    public function getScopeCode(): string
    {
        return $this->scopeCode;
    }
}
