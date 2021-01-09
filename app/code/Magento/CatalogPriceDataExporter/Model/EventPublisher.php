<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model;

use Psr\Log\LoggerInterface;
use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Class for publishing price events
 */
class EventPublisher
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param LoggerInterface $logger
     * @param PublisherInterface $publisher
     */
    public function __construct(
        LoggerInterface $logger,
        PublisherInterface $publisher
    ) {
        $this->logger = $logger;
        $this->publisher = $publisher;
    }

    /**
     * Publish event data
     *
     * @param array $events
     * @return void
     */
    public function publishEvents(array $events): void
    {
        $this->publisher->publish('export.product.prices', \json_encode($events));
        $this->logger->info(\json_encode($events));
    }
}
