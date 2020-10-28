<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product\MediaGallery;

/**
 * Media gallery image data formatter
 */
class ImageFormatter implements MediaGalleryFormatterInterface
{
    /**
     * @var MediaUrlProvider
     */
    private $mediaUrlProvider;

    /**
     * @param MediaUrlProvider $mediaUrlProvider
     */
    public function __construct(MediaUrlProvider $mediaUrlProvider)
    {
        $this->mediaUrlProvider = $mediaUrlProvider;
    }

    /**
     * @inheritdoc
     */
    public function format(array $row, array $roleImagesArray): array
    {
        $roleImages = $roleImagesArray[$row['storeViewCode']][$row['productId']];
        $roles =  \array_keys(\array_filter($roleImages), $row['file'], true);

        if (true === (bool)$row['disabled']) {
            $roles[] = self::ROLE_DISABLED;
        }

        return [
            'productId' => $row['productId'],
            'storeViewCode' => $row['storeViewCode'],
            'images' => [
                'resource' => [
                    'url' => $this->mediaUrlProvider->getBaseMediaUrlByStoreViewCode($row['storeViewCode'])
                        . $row['file'],
                    'label' => $row['label'] ?? '',
                    'roles' => $roles,
                ],
                'sortOrder' => (int)$row['sortOrder'],
            ],
        ];
    }
}
