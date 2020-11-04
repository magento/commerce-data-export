<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product\MediaGallery;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interface for media gallery formatters
 */
interface MediaGalleryFormatterInterface
{
    /**
     * Hide from PDP role
     */
    const ROLE_DISABLED = 'hide_from_pdp';

    /**
     * Format media gallery row
     *
     * @param array $row
     * @param array $roleImagesArray
     *
     * @return array
     *
     * @throws NoSuchEntityException
     */
    public function format(array $row, array $roleImagesArray): array;
}
