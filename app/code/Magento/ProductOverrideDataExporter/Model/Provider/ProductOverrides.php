<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ProductOverrideDataExporter\Model\Provider;

use Magento\Framework\ObjectManagerInterface;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Psr\Log\LoggerInterface;

/**
 * Class ProductOverrides
 * @package Magento\ProductOverride\Model\Provider\ProductOverrides
 */
class ProductOverrides
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string[]
     */
    private $overrideProviders;

    /**
     * ProductOverrides constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param array $overrideProviders
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        LoggerInterface $logger,
        array $overrideProviders = []
    ) {
        $this->objectManager = $objectManager;
        $this->logger = $logger;
        $this->overrideProviders = $overrideProviders;
    }

    /**
     * @param array $values
     * @return array
     */
    public function get(array $values) : array
    {
        $output = [];
        try {
            foreach ($this->overrideProviders as $providerClassName) {
                $provider = $this->objectManager->get($providerClassName);
                $output[] = $provider->get($values);
            }
        } catch (\Exception $exception) {
            $this->logger->critical($exception);
            throw new UnableRetrieveData('Unable retrieve product data');
        }
        return !empty($output) ? \array_merge(...$output) : [];
    }
}
