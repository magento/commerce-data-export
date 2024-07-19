<?php
/**
 * Copyright 2021 Adobe
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

namespace Magento\BundleProductDataExporter\Model\Provider\Product;

/**
 * Format new option uid in base64 encode for entered bundle options
 */
class BundleItemOptionUid
{
    /**
     * Bundle product option type name
     */
    public const OPTION_TYPE = 'bundle';

    /**
     * Returns uid based on option id, selection id and selection qty
     *
     * @param string $optionId
     * @param string $selectionId
     * @param string $selectionQty
     * @return string
     */
    public function resolve(string $optionId, string $selectionId, string $selectionQty): string
    {
        $optionDetails = [
            self::OPTION_TYPE,
            $optionId,
            $selectionId,
            (int)$selectionQty
        ];

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return \base64_encode(\implode('/', $optionDetails));
    }
}
