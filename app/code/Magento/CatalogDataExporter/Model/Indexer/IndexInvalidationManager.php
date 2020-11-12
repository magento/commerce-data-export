<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Indexer;

use Magento\Indexer\Model\Indexer;

/**
 * Class IndexInvalidationManager
 *
 * Invalidates indexes by preconfigured events
 */
class IndexInvalidationManager
{
    /**
     * @var Indexer
     */
    private $indexer;

    /**
     * @var array
     */
    private $invalidationEvents;

    /**
     * IndexInvalidationManager constructor.
     *
     * @param Indexer $indexer
     * @param array $invalidationEvents
     */
    public function __construct(
        Indexer $indexer,
        array $invalidationEvents
    ) {
        $this->indexer = $indexer;
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
        foreach ($indexers as $indexer) {
            $this->indexer->load($indexer)->invalidate();
        }
    }
}
