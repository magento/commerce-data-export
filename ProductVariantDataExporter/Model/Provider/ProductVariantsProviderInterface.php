<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Model\Provider;

use Magento\DataExporter\Exception\UnableRetrieveData;

/**
 * Interface ProductVariantsProviderInterface
 * @deprecated
 * @see \Magento\DataExporter\Export\DataProcessorInterface
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
