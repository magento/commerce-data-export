<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Test\Integration;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogPriceDataExporter\Model\EventBuilder;
use Magento\CatalogPriceDataExporter\Model\EventPool;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class AbstractProductPriceExportTest extends TestCase
{
    /**
     * @var  EventBuilder
     */
    private $eventBuilder;

    /**
     * @var EventPool
     */
    private $eventPool;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $objectManager = Bootstrap::getObjectManager();
        $this->eventBuilder = $objectManager->create(EventBuilder::class);
        $this->eventPool = $objectManager->create(EventPool::class);
        $this->productRepository = $objectManager->create(ProductRepositoryInterface::class);
    }

    /**
     * Retrieve price synchronization events
     *
     * @param array|null $priceTypes
     *
     * @return array
     *
     * @throws \Magento\DataExporter\Exception\UnableRetrieveData
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function retrieveEvents(?array $priceTypes = []): array
    {
        $resolvers = [];
        $events = [];
        if (empty($priceTypes)) {
            $resolvers = $this->eventPool->getFullReindexResolvers();
        } else {
            foreach ($priceTypes as $priceType) {
                $resolvers[] = $this->eventPool->getFullReindexResolver($priceType);
            }
        }
        foreach ($resolvers as $resolver) {
            foreach ($resolver->retrieve() as $eventData) {
                $events[] = $this->eventBuilder->build($eventData);
            }
        }
        return $events;
    }
}
