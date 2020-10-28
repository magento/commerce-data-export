<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product\ProductOptions;

/**
 * Downloadable links option value uid generator
 */
class DownloadableLinksOptionUid
{
    /**
     * Downloadable links Option type name
     */
    public const OPTION_TYPE = 'downloadable';

    /**
     * Returns uid based on link id
     *
     * @param string $linkId
     * @return string
     */
    public function resolve(string $linkId): string
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return base64_encode(implode('/', [self::OPTION_TYPE, $linkId]));
    }
}
