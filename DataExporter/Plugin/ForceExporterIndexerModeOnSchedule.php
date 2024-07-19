<?php
/**
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
 */
declare(strict_types=1);

namespace Magento\DataExporter\Plugin;

use Magento\DataExporter\Model\Indexer\FeedIndexer;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\Indexer\ActionFactory;
use Magento\Framework\Indexer\IndexerInterface;

/**
 * Plugin to intercept setScheduled method of indexers
 */
class ForceExporterIndexerModeOnSchedule
{
    private ActionFactory $actionFactory;
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @param ActionFactory $actionFactory
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(ActionFactory $actionFactory, CommerceDataExportLoggerInterface $logger)
    {
        $this->actionFactory = $actionFactory;
        $this->logger = $logger;
    }

    /**
     * Intercept the setScheduled method to disable Update on Save for exporter indexers
     *
     * @param IndexerInterface $indexer
     * @param callable $proceed
     * @param bool $scheduled
     * @return void
     */
    public function aroundSetScheduled(IndexerInterface $indexer, callable $proceed, bool $scheduled)
    {
        if ($scheduled === true) {
            return $proceed($scheduled);
        }

        try {
            $indexerAction = $this->actionFactory->create($indexer->getActionClass());

            // Check if indexer is one of the DataExporter indexer
            if ($indexerAction instanceof FeedIndexer) {
                $this->logger->notice(
                    __("Update on Save (realtime) is not allowed for this indexer: %1", $indexer->getTitle())
                );
                return;
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                'Data Exporter exception has occurred: ' . $e->getMessage(),
                ['exception' => $e]
            );
            return $proceed($scheduled);
        }

        return $proceed($scheduled);
    }
}
