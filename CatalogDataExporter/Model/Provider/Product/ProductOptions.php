<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
