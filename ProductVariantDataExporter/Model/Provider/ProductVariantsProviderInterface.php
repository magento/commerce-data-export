<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Model\Provider;

use Magento\DataExporter\Exception\UnableRetrieveData;

/**
 * Interface ProductVariantsProviderInterface
 */
interface ProductVariantsProviderInterface
{
    /**
     * Get product variants data
     *
     * @param array $values
     * @return array
     * @throws UnableRetrieveData
     */
    public function get(array $values) : array;
}
