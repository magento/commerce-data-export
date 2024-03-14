<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

use Magento\DataExporter\Model\FeedExportStatus;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\DataExporter\Status\ExportStatusCodeProvider;
use Magento\Framework\App\ResourceConnection;

class FeedUpdater
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var array
     */
    private array $feedTableColumns = [];
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        CommerceDataExportLoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * Execute data
     *
     * @param array $feedData
     * @param ?FeedExportStatus $exportStatus
     * @param FeedIndexMetadata $metadata
     * @param DataSerializerInterface $serializer
     */
    public function execute(
        array $feedData,
        ?FeedExportStatus $exportStatus,
        FeedIndexMetadata $metadata,
        DataSerializerInterface $serializer
    ): void {
        try {
            $connection = $this->resourceConnection->getConnection();

            $dataForInsert = $serializer->serialize($feedData, $exportStatus, $metadata);
            if (!empty($dataForInsert)) {
                $fieldsToUpdateOnDuplicate = array_intersect_key(
                    $metadata->getFeedTableMutableColumns(),
                    $this->getFeedTableColumns($metadata)
                );
                // Skip data insert if feed submit was skipped
                if (null !== $exportStatus
                    && $exportStatus->getStatus()->getValue() === ExportStatusCodeProvider::FEED_SUBMIT_SKIPPED) {
                    return;
                }
                $connection->insertOnDuplicate(
                    $this->resourceConnection->getTableName($metadata->getFeedTableName()),
                    $dataForInsert,
                    $fieldsToUpdateOnDuplicate
                );
                $this->logger->logProgress(null, count($dataForInsert));
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                'Cannot log export status to feed table',
                [
                    'export_status' => $exportStatus->getStatus(),
                    'export_failed_items' => $exportStatus->getFailedItems(),
                    'export_phrase' => $exportStatus->getReasonPhrase(),
                    'error' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * Get feed table columns
     *
     * @param FeedIndexMetadata $metadata
     * @return array
     */
    private function getFeedTableColumns(FeedIndexMetadata $metadata): array
    {
        if (!isset($this->feedTableColumns[$metadata->getFeedName()])) {
            $columns = array_keys(
                $this->resourceConnection->getConnection()->describeTable(
                    $this->resourceConnection->getTableName($metadata->getFeedTableName())
                )
            );
            $this->feedTableColumns[$metadata->getFeedName()] = array_combine($columns, $columns);
        }
        return $this->feedTableColumns[$metadata->getFeedName()];
    }
}
