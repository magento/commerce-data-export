<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product;

/**
 * Class Options
 *
 * General data provider for different types of options.
 */
class Options implements OptionProviderInterface
{
    /**
     * @var OptionProviderInterface[]
     */
    private $optionProviders;

    /**
     * Options constructor.
     *
     * @param OptionProviderInterface[] $optionProviders
     */
    public function __construct(
        array $optionProviders = []
    ) {
        $this->optionProviders = $optionProviders;
    }

    /**
     * @inheritDoc
     */
    public function get(array $values): array
    {
        $options = [];
        foreach ($this->optionProviders as $provider) {
            $options = $options + $provider->get($values);
        }
        return $options;
    }
}
