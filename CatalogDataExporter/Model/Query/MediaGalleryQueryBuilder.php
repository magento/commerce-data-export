<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Query;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\Store;

/**
 * Product media gallery query for catalog data exporter
 */
class MediaGalleryQueryBuilder
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var int
     */
    private $mediaGalleryAttributeId;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @param ResourceConnection $resourceConnection
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        MetadataPool $metadataPool
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->metadataPool = $metadataPool;
    }

    /**
     * Get query for provider
     * @param int[] $productIds
     * @param string $storeViewCode
     * @param string|null $mediaType
     * @return Select
     * @throws \Exception
     */
    public function getQuery(array $productIds, string $storeViewCode, ?string $mediaType = null) : Select
    {
        $connection = $this->resourceConnection->getConnection();
        $catalogProductTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $productEntityJoinField = $connection->getAutoIncrementField($catalogProductTable);
        $linkField = $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();

        $select = $connection->select()->from(
            [
                'main' => $this->resourceConnection->getTableName('catalog_product_entity_media_gallery'),
            ],
            ['mediaType' => 'media_type', 'file' => 'value']
        )->joinInner(
            [
                's' => $this->resourceConnection->getTableName('store'),
            ],
            $connection->quoteInto('s.code = ?', $storeViewCode),
            ['storeViewCode' => 's.code']
        )->joinInner(
            [
                'entity' => $this->resourceConnection->getTableName(
                    'catalog_product_entity_media_gallery_value_to_entity'
                ),
            ],
            'main.value_id = entity.value_id'
        )->joinLeft(
            [
                'value' => $this->resourceConnection->getTableName(
                    'catalog_product_entity_media_gallery_value'
                ),
            ],
            implode(
                ' AND ',
                [
                    'main.value_id = value.value_id',
                    'value.store_id = s.store_id',
                    'value.' . $linkField . ' = entity.' . $linkField
                ]
            ),
            []
        )->joinLeft(
            [
                'default_value' => $this->resourceConnection->getTableName(
                    'catalog_product_entity_media_gallery_value'
                ),
            ],
            implode(
                ' AND ',
                [
                    'main.value_id = default_value.value_id',
                    'default_value.store_id = ' . Store::DEFAULT_STORE_ID,
                    'default_value.' . $linkField . ' = entity.' . $linkField
                ]
            ),
            []
        )->joinInner(
            [
                'product_entity' => $catalogProductTable,
            ],
            \sprintf('product_entity.%1$s = entity.%1$s', $productEntityJoinField),
            ['productId' => 'product_entity.entity_id']
        )->columns(
            [
                'label' => $connection->getIfNullSql('value.label', 'default_value.label'),
                'sortOrder' => $connection->getIfNullSql('value.position', 'default_value.position'),
                'disabled' => $connection->getIfNullSql('value.disabled', 'default_value.disabled'),
            ]
        )->where(
            'main.attribute_id = ?',
            $this->getMediaGalleryAttributeId()
        )->where(
            'product_entity.entity_id IN (?)',
            $productIds
        );

        if (null === $mediaType || $mediaType === 'external-video') {
            $select = $this->joinVideoData($select);
        }

        if (null !== $mediaType) {
            $select->where('main.media_type = ?', $mediaType);
        }

        return $select;
    }

    /**
     * Join video data
     *
     * @param Select $select
     *
     * @return Select
     */
    private function joinVideoData(Select $select): Select
    {
        $connection = $this->resourceConnection->getConnection();

        return $select->joinLeft(
            [
                'value_video' => $this->resourceConnection->getTableName(
                    'catalog_product_entity_media_gallery_value_video'
                ),
            ],
            'main.value_id = value_video.value_id AND value_video.store_id = s.store_id',
            []
        )->joinLeft(
            [
                'default_value_video' => $this->resourceConnection->getTableName(
                    'catalog_product_entity_media_gallery_value_video'
                ),
            ],
            'main.value_id = default_value_video.value_id AND default_value_video.store_id = 0',
            []
        )->columns(
            [
                'videoProvider' => $connection->getIfNullSql('value_video.provider', 'default_value_video.provider'),
                'videoUrl' => $connection->getIfNullSql('value_video.url', 'default_value_video.url'),
                'videoTitle' => $connection->getIfNullSql('value_video.title', 'default_value_video.title'),
                'videoDescription' => $connection->getIfNullSql(
                    'value_video.description',
                    'default_value_video.description'
                ),
                'videoMetadata' => $connection->getIfNullSql('value_video.metadata', 'default_value_video.metadata'),
            ]
        );
    }

    /**
     * Get media gallery attribute id
     *
     * @return int
     */
    private function getMediaGalleryAttributeId() : int
    {
        if (null === $this->mediaGalleryAttributeId) {
            $connection = $this->resourceConnection->getConnection();

            $this->mediaGalleryAttributeId = (int)$connection->fetchOne(
                $connection->select()
                    ->from(['a' => $this->resourceConnection->getTableName('eav_attribute')], ['attribute_id'])
                    ->join(
                        ['t' => $this->resourceConnection->getTableName('eav_entity_type')],
                        't.entity_type_id = a.entity_type_id',
                        []
                    )
                    ->where('t.entity_table = ?', 'catalog_product_entity')
                    ->where('a.attribute_code = ?', 'media_gallery')
            );
        }

        return $this->mediaGalleryAttributeId;
    }
}
