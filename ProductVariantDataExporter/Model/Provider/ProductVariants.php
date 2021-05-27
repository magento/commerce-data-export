<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Model\Provider;

use Magento\DataExporter\Exception\UnableRetrieveData;

/**
 * Product variants data provider
 */
class ProductVariants implements ProductVariantsProviderInterface
{
    /**
     * @var ProductVariantsProviderInterface[]
     */
    private $variantsProviders;

    /**
     * @param ProductVariantsProviderInterface[] $variantsProviders
     */
    public function __construct(
        array $variantsProviders = []
    ) {
        $this->variantsProviders = $variantsProviders;
    }

    /**
     * @inheritdoc
     *
     * @throws UnableRetrieveData
     */
    public function get(array $values): array
    {
        $variants = [];
        foreach ($this->variantsProviders as $provider) {
            $variants = $variants + $provider->get($values);
        }
        return $variants;
    }
}
