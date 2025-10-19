<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryDataExporter\Model\Query;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Model\Query\ChangelogSelectQueryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Mview\View\Changelog;

/**
 * Query to get Inventory Stock Status change log select
 */
class ChangelogSelectQuery implements ChangelogSelectQueryInterface
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;
    private FeedIndexMetadata $metadata;

    /**
     * @param ResourceConnection $resourceConnection
     * @param FeedIndexMetadata $metadata
     */
    public function __construct(
        ResourceConnection  $resourceConnection,
        FeedIndexMetadata $metadata
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->metadata = $metadata;
    }

    /**
     * @param string $sourceTableName
     * @param string $sourceTableField
     * @param int $lastVersionId
     *
     * @return Select
     */
    public function getChangelogSelect(
        string $sourceTableName,
        string $sourceTableField,
        int $lastVersionId
    ): Select {
        $sourceEntityTableName = $this->resourceConnection->getTableName($this->metadata->getSourceTableName());
        $sourceTableName = $this->resourceConnection->getTableName($sourceTableName);
        $viewSourceLinkField = $this->metadata->getViewSourceLinkField();
        return $this->resourceConnection->getConnection()
            ->select()
            ->from(
                ['v' => $sourceTableName],
                []
            )->join(
                ['es' => $sourceEntityTableName],
                sprintf('v.%s = es.%s', $sourceTableField, $viewSourceLinkField),
                [$sourceTableField]
            )->distinct(true)
             ->where(sprintf('v.%s > ?', Changelog::VERSION_ID_COLUMN_NAME), $lastVersionId);
    }
}
