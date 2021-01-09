<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Provider\FullReindex;

use Magento\CatalogPriceDataExporter\Model\Provider\ProductPriceProviderInterface;
use Magento\DataExporter\Exception\UnableRetrieveData;

/**
 * Interface for product price events providers for full indexation
 */
interface FullReindexPriceProviderInterface extends ProductPriceProviderInterface
{
    /**
     * Retrieve product price event data.
     *
     * @return \Generator
     *
     * @throws UnableRetrieveData
     */
    public function retrieve(): \Generator;
}
