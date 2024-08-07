<?php
/**
 * Copyright 2022 Adobe
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

use Magento\CatalogDataExporter\Model\Provider\Product\ProductOptions\ProductOptionProviderInterface;

/**
 * Class ProductOptions.
 *
 * Collects product options using option data providers.
 */
class ProductOptions implements ProductOptionProviderInterface
{
    /**
     * @var array
     */
    private $optionProviderFactories;

    /**
     * @param array $optionProviderFactories
     */
    public function __construct(
        array $optionProviderFactories = []
    ) {
        $this->optionProviderFactories = $optionProviderFactories;
    }

    /**
     * @inheritDoc
     */
    public function get(array $values): array
    {
        $productOptions = [];
        foreach ($this->optionProviderFactories as $providerFactory) {
            /** @var \Magento\CatalogDataExporter\Model\Provider\Product\ProductOptions\ProductOptionProviderInterface $provider */
            $provider = $providerFactory->create();
            $productOptions += $provider->get($values);
        }
        return $productOptions;
    }
}
