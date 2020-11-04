<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product\MediaGallery;

/**
 * Media gallery video data formatter
 */
class VideoFormatter implements MediaGalleryFormatterInterface
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
            'videos' => [
                'preview' => [
                    'url' => $this->mediaUrlProvider->getBaseMediaUrlByStoreViewCode($row['storeViewCode'])
                        . $row['file'],
                    'label' => $row['label'] ?? '',
                    'roles' => $roles,
                ],
                'video' => $this->getVideoContent($row),
                'sortOrder' => (int)$row['sortOrder'],
            ],
        ];
    }

    /**
     * Get video content
     *
     * @param array $row
     *
     * @return array|null
     */
    private function getVideoContent(array $row) : ?array
    {
        $videoContent = \array_filter($row, function ($value, $field) {
            return !empty($value) && \strpos($field, 'video') === 0;
        }, ARRAY_FILTER_USE_BOTH);

        if ($videoContent) {
            $videoContent['mediaType'] = $row['mediaType'];
        }

        return $videoContent ?: null;
    }
}
