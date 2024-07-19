<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
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
        $this->providerFactories = $providerFactories;
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
