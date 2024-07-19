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

namespace Magento\CatalogDataExporter\Model\Indexer;

use Magento\Indexer\Model\IndexerFactory;

/**
 * Class IndexInvalidationManager
 *
 * Invalidates indexes by preconfigured events
 */
class IndexInvalidationManager
{
    /**
     * @var IndexerFactory
     */
    private $indexerFactory;

    /**
     * @var array
     */
    private $invalidationEvents;

    /**
     * IndexInvalidationManager constructor.
     *
     * @param IndexerFactory $indexerFactory
     * @param array $invalidationEvents
     */
    public function __construct(
        IndexerFactory $indexerFactory,
        array $invalidationEvents
    ) {
        $this->indexerFactory = $indexerFactory;
        $this->invalidationEvents = $invalidationEvents;
    }

    /**
     * Invalidates all indexes subscribed on event
     *
     * @param string $eventName
     */
    public function invalidate(string $eventName): void
    {
        $indexers = isset($this->invalidationEvents[$eventName]) ? $this->invalidationEvents[$eventName] : [];
        foreach ($indexers as $indexerId) {
            $this->indexerFactory->create()->load($indexerId)->invalidate();
        }
    }
}
