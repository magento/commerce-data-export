<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Model\Provider\ProductVariants;

/**
 * Product variant option value provider interface
 */
interface OptionValueInterface
{
    /**
     * Get product variant option value
     *
     * @param string $parentId
     * @param string $optionId
     * @param string $optionValueUid
     * @return string
     */
    public function resolve(string $parentId, string $optionId, string $optionValueUid) : string;
}
