<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

use Magento\DataExporter\Export\Processor as ExportProcessor;
use Magento\DataExporter\Model\FeedHashBuilder;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\DataExporter\Model\ExportFeedInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\SerializerInterface;

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
     * @param FeedUpdater $feedUpdater
     * @param ExportFeedInterface $exportFeedProcessor
     * @param FeedHashBuilder $hashBuilder
     * @param SerializerInterface $serializer
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ExportProcessor $exportProcessor,
        MarkRemovedEntitiesInterface $markRemovedEntities,
        FeedUpdater $feedUpdater,
        ExportFeedInterface $exportFeedProcessor,
        FeedHashBuilder $hashBuilder,
        SerializerInterface $serializer,
        CommerceDataExportLoggerInterface $logger
    ) {
        parent::__construct(
            $resourceConnection,
            $exportProcessor,
            $exportFeedProcessor,
            $feedUpdater,
            $hashBuilder,
            $serializer,
            $logger
        );
        $this->markRemovedEntities = $markRemovedEntities;
        $this->logger = $logger;
    }

    /**
     * @inerhitDoc
     *
     * @param FeedIndexMetadata $metadata
     * @param DataSerializerInterface $serializer
     * @param EntityIdsProviderInterface $idsProvider
     * @param array $ids
     * @param callable|null $callback
     * @return void
     */
    public function partialReindex(
        FeedIndexMetadata $metadata,
        DataSerializerInterface $serializer,
        EntityIdsProviderInterface $idsProvider,
        array $ids = [],
        callable $callback = null
    ): void {
        $callback = function () use ($ids, $metadata) {
            try {
                return $this->markRemovedEntities->execute($ids, $metadata);
            } catch (\Throwable $e) {
                $this->logger->error(
                    sprintf("Cannot delete feed items. product ids: %s", implode(', ', $ids)),
                    ['exception' => $e]
                );
            }
        };

        parent::partialReindex($metadata, $serializer, $idsProvider, $ids, $callback);
    }
}
