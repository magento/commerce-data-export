<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

use Magento\DataExporter\Export\Processor as ExportProcessor;
use Magento\DataExporter\Model\Batch\BatchGeneratorInterface;
use Magento\DataExporter\Model\FeedHashBuilder;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\DataExporter\Model\ExportFeedInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Indexer\Model\ProcessManagerFactory;

/**
 * Feed indexer processor strategy, support creation, updates and deletion of an entity
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FeedIndexProcessorCreateUpdateDelete extends FeedIndexProcessorCreateUpdate implements FeedIndexProcessorInterface
{
    /**
     * @var MarkRemovedEntitiesInterface
     */
    private MarkRemovedEntitiesInterface $markRemovedEntities;

    /**
     * @var CommerceDataExportLoggerInterface
     */
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
     * @param ?DeletedEntitiesProviderInterface $deletedEntitiesProvider
     * @param ?ProcessManagerFactory $processManagerFactory
     * @param ?BatchGeneratorInterface $batchGenerator
     * @param ?IndexStateProviderFactory $indexStateProviderFactory
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ExportProcessor $exportProcessor,
        MarkRemovedEntitiesInterface $markRemovedEntities,
        FeedUpdater $feedUpdater,
        ExportFeedInterface $exportFeedProcessor,
        FeedHashBuilder $hashBuilder,
        SerializerInterface $serializer,
        CommerceDataExportLoggerInterface $logger,
        ?DeletedEntitiesProviderInterface $deletedEntitiesProvider = null,
        ?ProcessManagerFactory $processManagerFactory = null,
        ?BatchGeneratorInterface $batchGenerator = null,
        ?IndexStateProviderFactory $indexStateProviderFactory = null
    ) {
        parent::__construct(
            $resourceConnection,
            $exportProcessor,
            $exportFeedProcessor,
            $feedUpdater,
            $hashBuilder,
            $serializer,
            $logger,
            $deletedEntitiesProvider,
            $processManagerFactory,
            $batchGenerator,
            $indexStateProviderFactory
        );
        $this->markRemovedEntities = $markRemovedEntities;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function partialReindex(
        FeedIndexMetadata $metadata,
        DataSerializerInterface $serializer,
        EntityIdsProviderInterface $idsProvider,
        array $ids = [],
        callable $callback = null,
        IndexStateProvider $indexState = null
    ): void {
        parent::partialReindex($metadata, $serializer, $idsProvider, $ids, $callback, $indexState);
        if (!$metadata->isExportImmediately()) {
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
}
