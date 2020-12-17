<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model;

use Magento\CatalogPriceDataExporter\Model\Provider\FullReindex\FullReindexPriceProviderInterface;
use Magento\CatalogPriceDataExporter\Model\Provider\PartialReindex\PartialReindexPriceProviderInterface;

/**
 * Pool of all existing price events data providers
 */
class EventPool
{
    /**
     * @var array
     */
    private $partialReindexProviders;

    /**
     * @var array
     */
    private $fullReindexProviders;

    /**
     * @param PartialReindexPriceProviderInterface[] $partialReindexProviders
     * @param FullReindexPriceProviderInterface[] $fullReindexProviders
     */
    public function __construct(
        array $partialReindexProviders = [],
        array $fullReindexProviders = []
    ) {
        $this->partialReindexProviders = $partialReindexProviders;
        $this->fullReindexProviders = $fullReindexProviders;
    }

    /**
     * Retrieve product price event data resolver.
     *
     * @param string $priceType
     *
     * @return PartialReindexPriceProviderInterface
     *
     * @throws \InvalidArgumentException
     */
    public function getPartialReindexResolver(string $priceType): PartialReindexPriceProviderInterface
    {
        if (!isset($this->partialReindexProviders[$priceType])) {
            throw new \InvalidArgumentException("Product price event for price type {$priceType} does not exist");
        }

        return $this->partialReindexProviders[$priceType];
    }

    /**
     * Retrieve product price event data resolver.
     *
     * @return FullReindexPriceProviderInterface[]
     *
     * @throws \InvalidArgumentException
     */
    public function getFullReindexResolvers(): array
    {
        return $this->fullReindexProviders;
    }
}
