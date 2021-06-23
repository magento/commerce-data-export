<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Model\Provider\ProductVariants;

/**
 * Product variant id provider interface
 */
interface IdInterface
{
    /**
     * Get product variant id
     *
     * @param string[] $params
     * @return string
     * @throws \InvalidArgumentException
     */
    public function resolve(array $params) : string;
}
