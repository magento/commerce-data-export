<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model;

use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\Exception\LocalizedException;

/**
 * Function for syncing price data
 */
class Synchronize
{
    /**
     * @var EventPool
     */
    private $eventPool;

    /**
     * @var EventBuilder
     */
    private $eventBuilder;

    /**
     * @var EventPublisher
     */
    private $eventPublisher;

    /**
     * @param EventPool $eventPool
     * @param EventBuilder $eventBuilder
     * @param EventPublisher $eventPublisher
     */
    public function __construct(
        EventPool $eventPool,
        EventBuilder $eventBuilder,
        EventPublisher $eventPublisher
    ) {
        $this->eventPool = $eventPool;
        $this->eventBuilder = $eventBuilder;
        $this->eventPublisher = $eventPublisher;
    }

    /**
     * Run price synchronisation
     *
     * @param array|null $priceTypes
     *
     * @return void
     *
     * @throws LocalizedException
     * @throws UnableRetrieveData
     */
    public function execute(?array $priceTypes = []): void
    {
        $resolvers = [];
        if (empty($priceTypes)) {
            $resolvers = $this->eventPool->getFullReindexResolvers();
        } else {
            foreach ($priceTypes as $priceType) {
                $resolvers[] = $this->eventPool->getFullReindexResolver($priceType);
            }
        }

        foreach ($resolvers as $resolver) {
            foreach ($resolver->retrieve() as $eventData) {
                $events = $this->eventBuilder->build($eventData);
                $this->eventPublisher->publishEvents($events);
            }
        }
    }
}
