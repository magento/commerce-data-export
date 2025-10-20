<?php
/**
 * Copyright 2021 Adobe
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

namespace Magento\DataExporter\Service;

use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Indexer\Model\IndexerFactory;

/**
 * Invalidates indexes by preconfigured events
 */
class IndexInvalidationManager
{
    /**
     * IndexInvalidationManager constructor.
     *
     * @param IndexerFactory $indexerFactory
     * @param array $invalidationEvents
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        private readonly IndexerFactory $indexerFactory,
        private readonly array $invalidationEvents,
        private readonly CommerceDataExportLoggerInterface $logger
    ) {
    }

    /**
     * Invalidates all indexes subscribed on event
     *
     * @param string $eventName
     */
    public function invalidate(string $eventName): void
    {
        $indexers = $this->invalidationEvents[$eventName] ?? [];
        try {
            foreach ($indexers as $indexerId) {
                $this->indexerFactory->create()->load($indexerId)->invalidate();
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'Data Exporter: cannot invalidate indexer for event',
                ['event' => $eventName, 'error' => $e->getMessage()]
            );
        }
    }
}
