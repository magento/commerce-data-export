<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

use Magento\DataExporter\Lock\FeedLockManager;
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
     * @var FeedLockManager|null
     */
    private ?FeedLockManager $lockManager;

    /**
     * @param FeedIndexProcessorInterface $processor
     * @param DataSerializerInterface $serializer
     * @param FeedIndexMetadata $feedIndexMetadata
     * @param EntityIdsProviderInterface $entityIdsProvider
     * @param CommerceDataExportLoggerInterface|null $logger
     * @param FeedLockManager|null $lockManager
     */
    public function __construct(
        FeedIndexProcessorInterface $processor,
        DataSerializerInterface $serializer,
        FeedIndexMetadata $feedIndexMetadata,
        EntityIdsProviderInterface $entityIdsProvider,
        ?CommerceDataExportLoggerInterface $logger = null,
        FeedLockManager $lockManager = null
    ) {
        $this->processor = $processor;
        $this->feedIndexMetadata = $feedIndexMetadata;
        $this->dataSerializer = $serializer;
        $this->entityIdsProvider = $entityIdsProvider;
        $this->logger = $logger ??
            ObjectManager::getInstance()->get(CommerceDataExportLoggerInterface::class);
        $this->lockManager = $lockManager ?? ObjectManager::getInstance()->get(FeedLockManager::class);
    }

    /**
     * Execute full indexation
     *
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function executeFull()
    {
        $operation = $this->feedIndexMetadata->isExportImmediately() ? 'full sync' : 'full reindex(legacy)';
        $this->logger->initSyncLog($this->feedIndexMetadata, $operation);

        $unlock = true;
        $feedName = $this->feedIndexMetadata->getFeedName();
        if (!$this->lockManager->lock($feedName, $operation)) {
            $lockedBy = $this->lockManager->getLockedByName($feedName);
            // CLI command may initialize full resync, in this case ignore lock and let parent caller to unlock process
            if ($lockedBy === $this->getResyncLockedByName()) {
                $unlock = false;
            } else {
                $this->logger->info(sprintf('operation skipped - process locked by "%s"', $lockedBy));
                // feed marked as "invalid" in "indexer_state" table will be marked as "valid"
                // it's done intentionally since current full reindex process should handle it.
                // If needed to keep feed as "invalid" exception should be thrown here.
                return ;
            }
        }

        try {
            $this->processor->fullReindex(
                $this->feedIndexMetadata,
                $this->dataSerializer,
                $this->entityIdsProvider
            );
        } finally {
            if ($unlock) {
                $this->lockManager->unlock($feedName);
            }
            $this->logger->complete();
        }
    }

    /**
     * @return string
     * @see \Magento\DataExporter\Lock\FeedLockManager::lock for name patter
     */
    private function getResyncLockedByName(): string
    {
        // pid used to guarantee caller and current are the same processes
        return sprintf('resync(%s)', getmypid());
    }

    /**
     * Execute partial indexation by ID list
     *
     * @param int[] $ids
     * @return void
     */
    public function executeList(array $ids)
    {
        $this->logWarningIfFeedIsNotLocked();
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
        $this->logWarningIfFeedIsNotLocked();
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
        $this->logWarningIfFeedIsNotLocked();
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

    private function logWarningIfFeedIsNotLocked()
    {
        if (!$this->lockManager->isLocked($this->feedIndexMetadata->getFeedName())) {
            $this->logger->warning(
                sprintf(
                    'Unexpected call: feed "%s" is not locked, trace: %s',
                    $this->feedIndexMetadata->getFeedName(),
                    (new \Exception())->getTraceAsString()
                )
            );
        }
    }
}
