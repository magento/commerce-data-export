<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class responsible for building event data array chunks
 */
class EventBuilder
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var EventKeyGenerator
     */
    private $eventKeyGenerator;

    /**
     * @var int
     */
    private $batchSize;

    /**
     * @param StoreManagerInterface $storeManager
     * @param EventKeyGenerator $eventKeyGenerator
     * @param int $batchSize
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        EventKeyGenerator $eventKeyGenerator,
        int $batchSize = 100
    ) {
        $this->storeManager = $storeManager;
        $this->eventKeyGenerator = $eventKeyGenerator;
        $this->batchSize = $batchSize;
    }

    /**
     * Build events data chunks.
     *
     * @param array $priceEvents
     *
     * @return array
     *
     * @throws LocalizedException
     */
    public function build(array $priceEvents) : array
    {
        $output = [];

        foreach ($priceEvents as $key => $data) {
            $metaData = $this->eventKeyGenerator->resolveKey($key);
            $websiteCode = $this->storeManager->getWebsite($metaData['website_id'])->getCode();

            foreach (\array_chunk($data, $this->batchSize) as $events) {
                $output[] = [
                    'meta' => [
                        'event_type' => $metaData['event_type'],
                        'website' => $websiteCode === WebsiteInterface::ADMIN_CODE ? null : $websiteCode,
                        'customer_group' => $metaData['customer_group_id'] ?: null,
                    ],
                    'data' => $events,
                ];
            }
        }

        return $output;
    }
}
