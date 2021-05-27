<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
