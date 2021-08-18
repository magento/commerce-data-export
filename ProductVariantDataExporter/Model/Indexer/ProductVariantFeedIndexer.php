<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Model\Indexer;

use Magento\DataExporter\Model\Indexer\DataSerializerInterface;
use Magento\DataExporter\Model\Indexer\EntityIdsProviderInterface;
use Magento\DataExporter\Model\Indexer\FeedIndexer;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Model\Indexer\FeedIndexProcessorInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\DataExporter\Export\Processor as ExportProcessor;
use Magento\Framework\App\ResourceConnection;

/**
 * Product variant export feed indexer class
 */
class ProductVariantFeedIndexer extends FeedIndexer
{
    /**
     * Product variant feed indexer id
     */
    public const INDEXER_ID = 'catalog_data_exporter_product_variants';

    /**
     * @var ExportProcessor
     */
    private $processor;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param FeedIndexProcessorInterface $processor
     * @param DataSerializerInterface $serializer
     * @param FeedIndexMetadata $feedIndexMetadata
     * @param EntityIdsProviderInterface $entityIdsProvider
     * @param ExportProcessor $exportProcessor
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        FeedIndexProcessorInterface $processor,
        DataSerializerInterface $serializer,
        FeedIndexMetadata $feedIndexMetadata,
        EntityIdsProviderInterface $entityIdsProvider,
        ExportProcessor $exportProcessor,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct($processor, $serializer, $feedIndexMetadata, $entityIdsProvider);
        $this->processor = $exportProcessor;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get Ids select
     *
     * @param int $lastKnownId
     * @return Select
     */
    private function getIdsSelect(int $lastKnownId): Select
    {
        $sourceTableField = $this->feedIndexMetadata->getRelationsTableChildField();
        $columnExpression = sprintf(
            's.%s',
            $sourceTableField
        );
        $whereClause = sprintf('s.%s > ?', $sourceTableField);
        $connection = $this->resourceConnection->getConnection();
        return $connection->select()
            ->from(
                ['s' => $this->resourceConnection->getTableName($this->feedIndexMetadata->getRelationsTableName())],
                [
                    $this->feedIndexMetadata->getFeedTableChildField() => 's.' . $sourceTableField
                ]
            )
            ->where($whereClause, $lastKnownId)
            ->order($columnExpression)
            ->limit($this->feedIndexMetadata->getBatchSize());
    }

    /**
     * Get all product IDs
     *
     * @return \Generator
     */
    private function getAllIds(): ?\Generator
    {
        $connection = $this->resourceConnection->getConnection();
        $lastKnownId = 0;
        $continueReindex = true;
        while ($continueReindex) {
            $ids = $connection->fetchAll($this->getIdsSelect((int)$lastKnownId));
            if (empty($ids)) {
                $continueReindex = false;
            } else {
                yield $ids;
                $lastKnownId = end($ids)[$this->feedIndexMetadata->getFeedTableChildField()];
            }
        }
    }

    /**
     * Execute full indexation
     *
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function executeFull(): void
    {
        foreach ($this->getAllIds() as $ids) {
            $this->process($ids);
        }
    }

    /**
     * Execute partial indexation by ID list
     *
     * @param int[] $ids
     * @return void
     */
    public function executeList(array $ids): void
    {
        $arguments = [];
        foreach ($ids as $id) {
            $arguments[] = [$this->feedIndexMetadata->getFeedTableChildField() => $id];
        }
        $this->process($arguments);
    }

    /**
     * Execute partial indexation by ID
     *
     * @param int $id
     * @return void
     */
    public function executeRow($id): void
    {
        $this->process([[$this->feedIndexMetadata->getFeedTableChildField() => $id]]);
    }

    /**
     * Execute materialization on ids entities
     *
     * @param int[] $ids
     * @return void
     * @api
     */
    public function execute($ids): void
    {
        $arguments = [];
        foreach ($ids as $id) {
            $arguments[] = [$this->feedIndexMetadata->getFeedTableChildField() => $id];
        }

        $this->process($arguments);
    }

    /**
     * Indexer feed data processor
     *
     * @param array $indexData
     * @return void
     */
    private function process($indexData = []): void
    {
        $childIds = \array_column($indexData, $this->feedIndexMetadata->getFeedTableChildField());
        $deleteIds = $this->getRemovedIds($childIds);
        $data = $this->processor->process($this->feedIndexMetadata->getFeedName(), $indexData);
        $chunks = array_chunk($data, $this->feedIndexMetadata->getBatchSize());
        $connection = $this->resourceConnection->getConnection();
        foreach ($chunks as $chunk) {
            $connection->insertOnDuplicate(
                $this->resourceConnection->getTableName($this->feedIndexMetadata->getFeedTableName()),
                $this->dataSerializer->serialize($chunk),
                $this->feedIndexMetadata->getFeedTableMutableColumns()
            );
        }
        $this->markRemoved($deleteIds);
    }

    /**
     * Fetch feed data
     *
     * @param array $ids
     * @return array
     */
    protected function fetchFeedDataIds(array $ids): array
    {
        $feedIdentity = $this->feedIndexMetadata->getFeedIdentity();
        $feedData = $this->feedPool->getFeed($this->feedIndexMetadata->getFeedName())->getFeedByProductIds($ids);
        $output = [];

        foreach ($feedData['feed'] as $feedItem) {
            $output[$feedItem[$feedIdentity]] = $feedItem[$feedIdentity];
        }
        return $output;
    }

    /**
     * Mark entities as removed
     *
     * @param array $ids
     * @return void
     */
    private function markRemoved(array $ids): void
    {
        $connection = $this->getConnection();
        $connection->update(
            $this->resourceConnection->getTableName($this->feedIndexMetadata->getFeedTableName()),
            ['is_deleted' => new \Zend_Db_Expr('1')],
            [\sprintf('%s IN (?)', $this->feedIndexMetadata->getFeedTableField()) => $ids]
        );
    }

    /**
     * Get removed variant ids by variant product id by comparing indexer entries with relations table.
     *
     * @param array $childIds
     * @return array
     */
    private function getRemovedIds(array $childIds): array
    {
        $connection = $this->getConnection();
        $joinField = $connection->getAutoIncrementField(
            $this->resourceConnection->getTableName($this->feedIndexMetadata->getSourceTableName())
        );
        $subSelect = $select = $connection->select()
            ->from(
                ['cpe' => $this->resourceConnection->getTableName($this->feedIndexMetadata->getSourceTableName())],
                [$joinField]
            )->where(
                \sprintf(
                    'cpe.%1$s = index.%2$s',
                    $this->feedIndexMetadata->getSourceTableField(),
                    $this->feedIndexMetadata->getFeedTableParentField()
                )
            );
        $select = $connection->select()
            ->from(
                ['index' => $this->resourceConnection->getTableName($this->feedIndexMetadata->getFeedTableName())],
                ['id']
            )
            ->joinLeft(
                ['cpr' => $this->resourceConnection->getTableName($this->feedIndexMetadata->getRelationsTableName())],
                \sprintf(
                    'cpr.%1$s = index.%2$s AND cpr.%3$s = (%4$s)',
                    $this->feedIndexMetadata->getRelationsTableChildField(),
                    $this->feedIndexMetadata->getFeedTableChildField(),
                    $this->feedIndexMetadata->getRelationsTableParentField(),
                    $subSelect->assemble()
                ),
                []
            )
            ->where(
                \sprintf(
                    'index.%s IN (?)',
                    $this->feedIndexMetadata->getFeedTableChildField()
                ),
                $childIds
            )
            ->where('index.is_deleted = 0')
            ->where(\sprintf('cpr.%s IS NULL', $this->feedIndexMetadata->getRelationsTableParentField()));
        return $connection->fetchCol($select);
    }

    /**
     * Get db connection
     *
     * @return AdapterInterface
     */
    private function getConnection(): AdapterInterface
    {
        return $this->resourceConnection->getConnection();
    }
}
