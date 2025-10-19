<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product;

/**
 * Class ProductShopperInputOptions.
 *
 * Collects ProductShopperInputOptions using data providers.
 */
class ProductShopperInputOptions implements ProductShopperInputOptionProviderInterface
{
    /**
     * @var array
     */
    private $providerFactories;

    /**
     * @param array $providerFactories
     */
    public function __construct(
        array $providerFactories = []
    ) {
        $this->providerFactories = array_filter($providerFactories);
    }

    /**
     * @inheritDoc
     */
    public function get(array $values): array
    {
        $productShopperInputOptions = [];
        foreach ($this->providerFactories as $providerFactory) {
            /** @var \Magento\CatalogDataExporter\Model\Provider\Product\ProductShopperInputOptionProviderInterface $provider */
            $provider = $providerFactory->create();
            $productShopperInputOptions = $productShopperInputOptions + $provider->get($values);
        }
        return $productShopperInputOptions;
    }
}
