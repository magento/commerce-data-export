<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Indexer\ActionInterface as IndexerActionInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;

/**
 * Product export feed indexer class
 * Facade for IndexerProcessor, implements Magento native indexers interfaces
 */
class FeedIndexer implements IndexerActionInterface, MviewActionInterface, FeedIndexMetadataProviderInterface
{
    /**
     * @var FeedIndexProcessorCreateUpdate
     */
    private $processor;

    /**
     * @var FeedIndexMetadata
     */
    protected $feedIndexMetadata;

    /**
     * @var DataSerializerInterface
     */
    protected $dataSerializer;

    /**
     * @var EntityIdsProviderInterface
     */
    private $entityIdsProvider;

    /**
     * @var CommerceDataExportLoggerInterface
     */
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @param FeedIndexProcessorInterface $processor
     * @param DataSerializerInterface $serializer
     * @param FeedIndexMetadata $feedIndexMetadata
     * @param EntityIdsProviderInterface $entityIdsProvider
     */
    public function __construct(
        FeedIndexProcessorInterface $processor,
        DataSerializerInterface $serializer,
        FeedIndexMetadata $feedIndexMetadata,
        EntityIdsProviderInterface $entityIdsProvider,
        ?CommerceDataExportLoggerInterface $logger = null
    ) {
        $this->processor = $processor;
        $this->feedIndexMetadata = $feedIndexMetadata;
        $this->dataSerializer = $serializer;
        $this->entityIdsProvider = $entityIdsProvider;
        $this->logger = $logger ??
            ObjectManager::getInstance()->get(CommerceDataExportLoggerInterface::class);
    }

    /**
     * Execute full indexation
     *
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function executeFull()
    {
        $this->processor->fullReindex(
            $this->feedIndexMetadata,
            $this->dataSerializer,
            $this->entityIdsProvider
        );
    }

    /**
     * Execute partial indexation by ID list
     *
     * @param int[] $ids
     * @return void
     */
    public function executeList(array $ids)
    {
        $this->processor->partialReindex(
            $this->feedIndexMetadata,
            $this->dataSerializer,
            $this->entityIdsProvider,
            $ids
        );
        // track iteration completion
        $this->logger->logProgress();
    }

    /**
     * Execute partial indexation by ID
     *
     * @param int $id
     * @return void
     */
    public function executeRow($id)
    {
        $this->processor->partialReindex(
            $this->feedIndexMetadata,
            $this->dataSerializer,
            $this->entityIdsProvider,
            [$id]
        );
    }

    /**
     * Execute materialization on ids entities
     *
     * @param int[] $ids
     * @return void
     * @api
     */
    public function execute($ids)
    {
        $this->processor->partialReindex(
            $this->feedIndexMetadata,
            $this->dataSerializer,
            $this->entityIdsProvider,
            $ids
        );
        // track iteration completion
        $this->logger->logProgress();
    }

    /**
     * @inheritDoc
     */
    public function getFeedIndexMetadata(): FeedIndexMetadata
    {
        return $this->feedIndexMetadata;
    }
}
