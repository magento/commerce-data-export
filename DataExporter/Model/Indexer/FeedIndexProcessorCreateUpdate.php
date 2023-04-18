<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

use Magento\Framework\App\ResourceConnection;
use \Magento\DataExporter\Export\Processor as ExportProcessor;

/**
 * Base implementation of feed indexing behaviour, does not care about deleted entities
 */
class FeedIndexProcessorCreateUpdate implements FeedIndexProcessorInterface
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var ExportProcessor
     */
    private ExportProcessor $exportProcessor;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ExportProcessor $exportProcessor
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ExportProcessor $exportProcessor
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->exportProcessor = $exportProcessor;
    }

    /**
     * {@inerhitDoc}
     *
     * @param FeedIndexMetadata $metadata
     * @param DataSerializerInterface $serializer
     * @param EntityIdsProviderInterface $idsProvider
     * @param array $ids
     */
    public function partialReindex(
        FeedIndexMetadata $metadata,
        DataSerializerInterface $serializer,
        EntityIdsProviderInterface $idsProvider,
        array $ids = []
    ): void {
        $feedIdentity = $metadata->getFeedIdentity();
        $arguments = [];
        foreach ($idsProvider->getAffectedIds($metadata, $ids) as $id) {
            $arguments[] = [$feedIdentity => $id];
        }
        $data = $this->exportProcessor->process($metadata->getFeedName(), $arguments);
        $chunks = array_chunk($data, $metadata->getBatchSize());
        $connection = $this->resourceConnection->getConnection();
        foreach ($chunks as $chunk) {
            $connection->insertOnDuplicate(
                $this->resourceConnection->getTableName($metadata->getFeedTableName()),
                $serializer->serialize($chunk),
                $metadata->getFeedTableMutableColumns()
            );
        }
    }

    /**
     * {@inerhitDoc}
     *
     * @param FeedIndexMetadata $metadata
     * @param DataSerializerInterface $serializer
     * @param EntityIdsProviderInterface $idsProvider
     * @throws \Zend_Db_Statement_Exception
     */
    public function fullReindex(
        FeedIndexMetadata $metadata,
        DataSerializerInterface $serializer,
        EntityIdsProviderInterface $idsProvider
    ): void {
        $this->truncateIndexTable($metadata);
        foreach ($idsProvider->getAllIds($metadata) as $batch) {
            $ids = \array_column($batch, $metadata->getFeedIdentity());
            $this->partialReindex($metadata, $serializer, $idsProvider, $ids);
        }
    }

    /**
     * Truncates index table
     *
     * @param FeedIndexMetadata $metadata
     */
    private function truncateIndexTable(FeedIndexMetadata $metadata): void
    {
        if (!$metadata->isTruncateFeedOnFullReindex()) {
            return ;
        }
        $connection = $this->resourceConnection->getConnection();
        $feedTable = $this->resourceConnection->getTableName($metadata->getFeedTableName());
        $connection->truncateTable($feedTable);
    }
}
