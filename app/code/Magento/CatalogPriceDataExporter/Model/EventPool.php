<?php

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model;

use Magento\CatalogPriceDataExporter\Model\Event\ProductPriceEventInterface;

/**
 * Pool of all existing price events data providers
 */
class EventPool
{
    /**
     * @var array
     */
    private $priceTypeMap;

    /**
     * @param ProductPriceEventInterface[] $priceTypeMap
     */
    public function __construct(array $priceTypeMap = [])
    {
        $this->priceTypeMap = $priceTypeMap;
    }

    /**
     * Retrieve product price event data resolver.
     *
     * @param string $priceType
     *
     * @return ProductPriceEventInterface
     *
     * @throws \InvalidArgumentException
     */
    public function getEventResolver(string $priceType): ProductPriceEventInterface
    {
        if (!isset($this->priceTypeMap[$priceType])) {
            throw new \InvalidArgumentException("Product price event for price type {$priceType} does not exist");
        }

        return $this->priceTypeMap[$priceType];
    }
}
