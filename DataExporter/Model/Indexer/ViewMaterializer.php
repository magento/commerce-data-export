<?php
/*************************************************************************
 *
 * Copyright 2023 Adobe
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
 * ***********************************************************************
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

use Magento\DataExporter\Model\Batch\BatchGeneratorInterface;
use Magento\DataExporter\Model\FeedPool;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\Mview\ActionFactory;
use Magento\Framework\Mview\ActionInterface;
use Magento\Framework\Mview\View\ChangelogTableNotExistsException;
use Magento\Framework\Mview\View\StateInterface;
use Magento\Framework\Mview\ViewInterface;
use Magento\Indexer\Model\ProcessManagerFactory;

/**
 * Materializes view by IDs from changelog in parallel.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ViewMaterializer
{
    /**
     * @var ActionFactory
     */
    private ActionFactory $actionFactory;

    /**
     * @var CommerceDataExportLoggerInterface
     */
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @var ProcessManagerFactory
     */
    private ProcessManagerFactory $processManagerFactory;

    /**
     * @var BatchGeneratorInterface
     */
    private BatchGeneratorInterface $batchGenerator;

    /**
     * @var FeedPool
     */
    private FeedPool $feedPool;

    /**
     * @param ActionFactory $actionFactory
     * @param CommerceDataExportLoggerInterface $logger
     * @param BatchGeneratorInterface $batchGenerator
     * @param ProcessManagerFactory $processManagerFactory
     * @param FeedPool $feedPool
     */
    public function __construct(
        ActionFactory                     $actionFactory,
        CommerceDataExportLoggerInterface $logger,
        BatchGeneratorInterface           $batchGenerator,
        ProcessManagerFactory             $processManagerFactory,
        FeedPool                          $feedPool
    ) {
        $this->actionFactory = $actionFactory;
        $this->logger = $logger;
        $this->batchGenerator = $batchGenerator;
        $this->processManagerFactory = $processManagerFactory;
        $this->feedPool = $feedPool;
    }

    /**
     * Materialize view by IDs from changelog
     *
     * @param ViewInterface $view
     * @return void
     * @throws \Throwable
     */
    public function execute(ViewInterface $view): void
    {
        if (!$view->isIdle() || !$view->isEnabled()) {
            return;
        }

        try {
            $currentVersionId = $view->getChangelog()->getVersion();
        } catch (ChangelogTableNotExistsException $e) {
            return;
        }

        $lastVersionId = (int)$view->getState()->getVersionId();
        if ($lastVersionId >= $currentVersionId) {
            return;
        }

        try {
            $view->getState()->setStatus(StateInterface::STATUS_WORKING)->save();

            $this->executeAction($view);

            $view->getState()->loadByView($view->getId());
            $statusToRestore = $view->getState()->getStatus() === StateInterface::STATUS_SUSPENDED
                ? StateInterface::STATUS_SUSPENDED
                : StateInterface::STATUS_IDLE;
            $view->getState()->setVersionId($currentVersionId)->setStatus($statusToRestore)->save();
        } catch (\Throwable $exception) {
            $view->getState()->loadByView($view->getId());
            $statusToRestore = $view->getState()->getStatus() === StateInterface::STATUS_SUSPENDED
                ? StateInterface::STATUS_SUSPENDED
                : StateInterface::STATUS_IDLE;
            $view->getState()->setStatus($statusToRestore)->save();
            if (!$exception instanceof \Exception) {
                $exception = new \RuntimeException(
                    'Error when updating an mview',
                    0,
                    $exception
                );
            }
            throw $exception;
        }
    }

    /**
     * Execute view action from last version to current version, by batches
     *
     * @param ViewInterface $view
     * @return void
     * @throws \Exception
     */
    private function executeAction(ViewInterface $view)
    {
        $action = $this->actionFactory->get($view->getActionClass());
        $feedMetadata = $this->getFeedIndexMetadata($action);

        $batchIterator = $this->batchGenerator->generate($feedMetadata, ['viewId' => $view->getId()]);
        $threadCount = min($feedMetadata->getThreadCount(), $batchIterator->count());
        $userFunctions = [];
        for ($threadNumber = 1; $threadNumber <= $threadCount; $threadNumber++) {
            $userFunctions[] = function () use ($action, $batchIterator) {
                try {
                    // phpcs:disable Generic.Formatting.DisallowMultipleStatements.SameLine
                    // phpcs:ignore Generic.CodeAnalysis.ForLoopWithTestFunctionCall
                    for ($batchIterator->rewind(); $batchIterator->valid(); $batchIterator->next()) {
                        $ids = $batchIterator->current();
                        $action->execute($ids);
                    }
                    // phpcs:enable Generic.Formatting.DisallowMultipleStatements.SameLine
                } catch (\Throwable $e) {
                    $batchIterator->markBatchForRetry();
                    $this->logger->error(
                        'Mview Data Exporter exception has occurred: ' . $e->getMessage(),
                        ['exception' => $e]
                    );
                }
            };
        }

        $processManager = $this->processManagerFactory->create(['threadsCount' => $threadCount]);
        $processManager->execute($userFunctions);
    }

    /**
     * Returns feed metadata by mview action object
     *
     * @param ActionInterface $action
     * @return FeedIndexMetadata
     */
    private function getFeedIndexMetadata(ActionInterface $action): FeedIndexMetadata
    {
        if ($action instanceof FeedIndexMetadataProviderInterface) {
            return $action->getFeedIndexMetadata();
        } else {
            throw new \InvalidArgumentException(
                \sprintf('Feed for the "%s" action class is not registered', $action::class)
            );
        }
    }
}
