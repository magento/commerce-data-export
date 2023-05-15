<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\App\ResourceConnection;
use \Magento\DataExporter\Export\Processor as ExportProcessor;

/**
 * Base implementation of feed indexing behaviour, does not care about deleted entities
 */
class FeedIndexProcessorCreateUpdate implements FeedIndexProcessorInterface
{
    private ResourceConnection $resourceConnection;
    private ExportProcessor $exportProcessor;
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ExportProcessor $exportProcessor
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ExportProcessor $exportProcessor,
        CommerceDataExportLoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->exportProcessor = $exportProcessor;
        $this->logger = $logger;
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
        try {
            $this->truncateIndexTable($metadata);
            foreach ($idsProvider->getAllIds($metadata) as $batch) {
                $ids = \array_column($batch, $metadata->getFeedIdentity());
                $this->partialReindex($metadata, $serializer, $idsProvider, $ids);
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                'Data Exporter exception has occurred: ' . $e->getMessage(),
                ['exception' => $e]
            );
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
