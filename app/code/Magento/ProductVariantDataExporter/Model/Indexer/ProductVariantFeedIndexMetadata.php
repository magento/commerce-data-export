<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Model\Indexer;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;

/**
 * Product variant feed indexer metadata provider
 */
class ProductVariantFeedIndexMetadata extends FeedIndexMetadata
{
    /**
     * @var string|null
     */
    protected $feedTableParentField;

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
     * @param string|null $feedTableParentField
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
        int $batchSize = 100,
        string $feedTableParentField = null
    ) {
        parent::__construct(
            $feedName,
            $sourceTableName,
            $sourceTableField,
            $feedIdentity,
            $feedTableName,
            $feedTableField,
            $feedTableMutableColumns,
            $scopeTableName,
            $scopeTableField,
            $scopeCode,
            $batchSize
        );
        $this->feedTableParentField = $feedTableParentField;
    }

    /**
     * Get feed table product field
     *
     * @return string
     */
    public function getFeedTableParentField(): string
    {
        return $this->feedTableParentField;
    }
}
