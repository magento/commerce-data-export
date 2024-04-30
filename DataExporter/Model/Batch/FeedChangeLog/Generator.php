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

namespace Magento\DataExporter\Model\Batch\FeedChangeLog;

use Magento\DataExporter\Model\Batch\BatchGeneratorInterface;
use Magento\DataExporter\Model\Batch\BatchIteratorInterface;
use Magento\DataExporter\Model\Batch\BatchTableFactory;
use Magento\DataExporter\Model\Batch\BatchLocatorFactory;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\DataExporter\Model\Logging\LogRegistry;
use Magento\DataExporter\Model\Provider\ChangelogQueryProvider;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Mview\ViewFactory;
use Magento\Framework\Mview\ViewInterface;

/**
 * Creates batches based on feed change log table and configured batch size.
 * Used in the following scenarios:
 * - partial entity update, triggered by `indexer_update_all_views` cron job
 */
class Generator implements BatchGeneratorInterface
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var IteratorFactory
     */
    private IteratorFactory $iteratorFactory;

    /**
     * @var BatchLocatorFactory
     */
    private BatchLocatorFactory $batchLocatorFactory;

    /**
     * @var BatchTableFactory
     */
    private BatchTableFactory $batchTableFactory;

    /**
     * @var ViewFactory
     */

    private ViewFactory $viewFactory;

    /**
     * @var CommerceDataExportLoggerInterface
     */
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @var ChangelogQueryProvider
     */
    private ChangelogQueryProvider $changeLogQueryProvider;

    /**
     * @param ResourceConnection $resourceConnection
     * @param IteratorFactory $iteratorFactory
     * @param BatchLocatorFactory $batchLocatorFactory
     * @param BatchTableFactory $batchTableFactory
     * @param ViewFactory $viewFactory
     * @param CommerceDataExportLoggerInterface $logger
     * @param ChangelogQueryProvider $changeLogQueryProvider
     */
    public function __construct(
        ResourceConnection  $resourceConnection,
        IteratorFactory     $iteratorFactory,
        BatchLocatorFactory $batchLocatorFactory,
        BatchTableFactory   $batchTableFactory,
        ViewFactory         $viewFactory,
        CommerceDataExportLoggerInterface $logger,
        ChangelogQueryProvider $changeLogQueryProvider
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->iteratorFactory = $iteratorFactory;
        $this->batchLocatorFactory = $batchLocatorFactory;
        $this->batchTableFactory = $batchTableFactory;
        $this->viewFactory = $viewFactory;
        $this->logger = $logger;
        $this->changeLogQueryProvider = $changeLogQueryProvider;
    }

    /**
     * @inheritDoc
     */
    public function generate(FeedIndexMetadata $metadata, array $args = []): BatchIteratorInterface
    {
        try {
            $batchIterator = $this->doGenerate($metadata, $args);
            $this->logger->addContext([LogRegistry::TOTAL_ITERATIONS => $batchIterator->count()]);
            return $batchIterator;
        } catch (\Throwable $e) {
            // TODO: throw exception and check it will not impact on cron:run
            $this->logger->error(
                sprintf(
                    '%s feed: error occurred: %s',
                    $metadata->getFeedName(),
                    $e->getMessage()
                ),
                ['exception' => $e]
            );
            throw $e;
        }
    }

    /**
     * Generate batches based on feed change log table.
     *
     * @param FeedIndexMetadata $metadata
     * @param array $args
     * @return BatchIteratorInterface
     * @throws \Zend_Db_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    private function doGenerate(FeedIndexMetadata $metadata, array $args = []): BatchIteratorInterface
    {
        $connection = $this->resourceConnection->getConnection();
        if ($connection instanceof \Magento\ResourceConnections\DB\Adapter\Pdo\MysqlProxy) {
            $connection->setUseMasterConnection();
        }
        $viewId = $args['viewId'] ?? $metadata->getFeedTableName();
        $view = $this->viewFactory->create()->load($viewId);
        $sourceTableName = $this->resourceConnection->getTableName($view->getChangelog()->getName());
        $sourceTableField = $view->getChangelog()->getColumnName();

        $sequenceTableName = $this->resourceConnection->getTableName(sprintf("%s_cl_index_sequence", $viewId));
        $batchLocator = $this->batchLocatorFactory->create(['sequenceTableName' => $sequenceTableName]);
        $batchLocator->init();

        $batchTableName = $this->resourceConnection->getTableName(sprintf("%s_cl_index_batches", $viewId));
        $batchTable = $this->batchTableFactory->create(
            [
                'batchTableName' => $batchTableName,
                'sourceTableName' => $sourceTableName,
                'sourceTableKeyColumns' => [$sourceTableField]
            ]
        );
        $insertDataQuery = $connection->insertFromSelect(
            $this->getSelect(
                $view,
                $sourceTableName,
                $sourceTableField,
                $batchTable->getBatchNumberField(),
                $metadata->getBatchSize()
            ),
            $batchTable->getBatchTableName(),
            [$batchTable->getBatchNumberField(), $sourceTableField]
        );
        $totalProcessedItems = $batchTable->create($insertDataQuery);

        $this->logger->info(
            $totalProcessedItems > 0
                ? sprintf(
                'start processing `%s` items in `%s` threads',
                $totalProcessedItems,
                $metadata->getThreadCount()
            )
                : sprintf(
                'nothing to process - no items to sync. Not expected? Are there any items in source table `%s`?',
                $sourceTableName
            )
        );

        $batchIterator = $this->iteratorFactory->create(
            [
                'batchTable' => $batchTable,
                'sourceTableName' => $sourceTableName,
                'sourceTableKeyColumn' => $sourceTableField,
                'batchLocator' => $batchLocator,
            ]
        );

        return $batchIterator;
    }

    /**
     * Returns select for batch table.
     *
     * @param ViewInterface $view
     * @param string $sourceTableName
     * @param string $sourceTableField
     * @param string $batchNumField
     * @param int $batchSize
     * @return Select
     */
    private function getSelect(
        ViewInterface $view,
        string $sourceTableName,
        string $sourceTableField,
        string $batchNumField,
        int $batchSize
    ): Select {
        $connection = $this->resourceConnection->getConnection();
        $lastVersionId = (int)$view->getState()->getVersionId();
        $changeLogQuery = $this->changeLogQueryProvider->getChangeLogSelectQuery($view->getId());
        $subSelect = $changeLogQuery->getChangelogSelect(
            $sourceTableName,
            $sourceTableField,
            $lastVersionId
        );

        $select = $connection->select()
            ->from(
                ['t' => new \Zend_Db_Expr((sprintf('(%s)', $subSelect)))],
                [
                    $batchNumField => new \Zend_Db_Expr(
                        sprintf(
                            "CEILING(ROW_NUMBER() OVER (ORDER BY %s) / %d)",
                            $sourceTableField,
                            $batchSize
                        )
                    ),
                    $sourceTableField
                ]
            );

        return $select;
    }
}
