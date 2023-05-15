<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

use Magento\DataExporter\Export\Processor as ExportProcessor;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Feed indexer processor strategy, support creation, updates and deletion of an entity
 */
class FeedIndexProcessorCreateUpdateDelete extends FeedIndexProcessorCreateUpdate implements FeedIndexProcessorInterface
{
    private MarkRemovedEntitiesInterface $markRemovedEntities;
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ExportProcessor $exportProcessor
     * @param MarkRemovedEntitiesInterface $markRemovedEntities
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ExportProcessor $exportProcessor,
        MarkRemovedEntitiesInterface $markRemovedEntities,
        CommerceDataExportLoggerInterface $logger
    ) {
        parent::__construct($resourceConnection, $exportProcessor, $logger);
        $this->markRemovedEntities = $markRemovedEntities;
        $this->logger = $logger;
    }

    /**
     * @inheridoc
     *
     * @param FeedIndexMetadata $metadata
     * @param DataSerializerInterface $serializer
     * @param EntityIdsProviderInterface $idsProvider
     * @param array $ids
     */
    public function partialReindex(FeedIndexMetadata $metadata, DataSerializerInterface $serializer, EntityIdsProviderInterface $idsProvider, array $ids = []): void
    {
        parent::partialReindex($metadata, $serializer, $idsProvider, $ids);

        try {
            $this->markRemovedEntities->execute($ids, $metadata);
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf("Cannot delete feed items. product ids: %s", implode(', ', $ids)),
                ['exception' => $e]
            );
        }
    }
}
