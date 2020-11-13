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
     * @param int $batchSize
     * @param string|null $feedTableParentField
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
