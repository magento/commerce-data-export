<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Provider\PartialReindex;

use Magento\CatalogPriceDataExporter\Model\Provider\ProductPriceProviderInterface;
use Magento\DataExporter\Exception\UnableRetrieveData;

/**
 * Interface for product price events providers for partial indexation
 */
interface PartialReindexPriceProviderInterface extends ProductPriceProviderInterface
{

    /**
     * Retrieve product price event data.
     *
     * @param array $indexData
     *
     * @return \Generator
     *
     * @throws UnableRetrieveData
     */
    public function retrieve(array $indexData): \Generator;
}
