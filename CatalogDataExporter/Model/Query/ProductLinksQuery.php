<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\Expression;
use Magento\GroupedProduct\Model\ResourceModel\Product\Link as GroupedProductLink;

/**
 * Product links query for catalog data exporter
 */
class ProductLinksQuery
{
    /**
     * @see \Magento\Catalog\Model\Product\Link::LINK_TYPE_RELATED
     */
    private const LINK_TYPE_RELATED = 1;

    /**
     * @see \Magento\Catalog\Model\Product\Link::LINK_TYPE_UPSELL
     */
    private const LINK_TYPE_UPSELL = 4;

    /**
     * @see \Magento\Catalog\Model\Product\Link::LINK_TYPE_CROSSSELL
     */
    private const LINK_TYPE_CROSSSELL = 5;

    /**
     * Link types array
     *
     * @var array
     */
    private $linkTypes = [
        self::LINK_TYPE_RELATED,
        self::LINK_TYPE_UPSELL,
        self::LINK_TYPE_CROSSSELL,
    ];

    /**
     * @var array
     */
    private $productLinkAttributesCache;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get query for provider
     *
     * @param int[] $productIds
     * @param string $storeViewCode
     * @param int|null $linkTypeId
     *
     * @return Select
     */
    public function getQuery(array $productIds, string $storeViewCode, int $linkTypeId = null) : Select
    {
        $connection = $this->resourceConnection->getConnection();
        $catalogProductTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $productEntityJoinField = $connection->getAutoIncrementField($catalogProductTable);

        $select = $connection->select()
            ->from(
                [
                    'cpe_product' => $catalogProductTable
                ],
                []
            )
            ->join(
                [
                    's' => $this->resourceConnection->getTableName('store'),
                ],
                $connection->quoteInto('s.code = ?', $storeViewCode),
                []
            )
            ->join(
                [
                    'links' => $this->resourceConnection->getTableName('catalog_product_link')
                ],
                'links.linked_product_id = cpe_product.entity_id',
                []
            )
            ->join(
                [
                    'cpe_parent' => $catalogProductTable
                ],
                \sprintf('cpe_parent.%1$s = links.product_id', $productEntityJoinField),
                []
            )
            ->join(
                [
                    'parent_website' => $this->resourceConnection->getTableName('catalog_product_website')
                ],
                'parent_website.product_id = cpe_parent.entity_id AND parent_website.website_id = s.website_id',
                []
            )
            ->join(
                [
                    'link_website' => $this->resourceConnection->getTableName('catalog_product_website')
                ],
                'link_website.product_id = links.linked_product_id AND link_website.website_id = s.website_id',
                []
            )
            ->columns(
                [
                    'parentId' => 'cpe_parent.entity_id',
                    'productId' => 'cpe_product.entity_id',
                    'sku' => 'cpe_product.sku',
                    'link_type_id' => 'links.link_type_id',
                ]
            )
            ->where('cpe_parent.entity_id IN (?)', $productIds)
            ->where('links.link_type_id IN (?)', $linkTypeId != null ? $linkTypeId : $this->linkTypes);

        if ($linkTypeId === GroupedProductLink::LINK_TYPE_GROUPED) {
            foreach ($this->getProductLinkAttributesData($linkTypeId) as $attributeData) {
                $tableAlias = \sprintf('attribute_%s', $attributeData['code']);

                $select->joinLeft(
                    [
                        $tableAlias => $this->resourceConnection->getTableName(
                            ['catalog_product_link_attribute', $attributeData['data_type']]
                        ),
                    ],
                    \sprintf(
                        '%1$s.link_id = links.link_id AND %1$s.product_link_attribute_id IN (%2$s)',
                        $tableAlias,
                        $attributeData['attribute_ids']
                    ),
                    [
                        $attributeData['code'] => \sprintf('%s.value', $tableAlias),
                    ]
                );
            }
        }

        return $select;
    }

    /**
     * Retrieve product link attributes data
     *
     * @param int|null $linkTypeId
     * @return array
     */
    private function getProductLinkAttributesData(int $linkTypeId = null) : array
    {
        if (null === $this->productLinkAttributesCache) {
            $connection = $this->resourceConnection->getConnection();

            $select = $connection->select()
                ->from(['cpla' => $this->resourceConnection->getTableName('catalog_product_link_attribute')])
                ->columns(
                    [
                        'attribute_ids' => new Expression('GROUP_CONCAT(cpla.product_link_attribute_id)'),
                        'code' => 'cpla.product_link_attribute_code',
                        'data_type' => 'cpla.data_type'
                    ]
                )
                ->where('cpla.product_link_attribute_code IN (?)', ['position', 'qty'])
                ->where('cpla.link_type_id IN (?)', $linkTypeId != null ? $linkTypeId : $this->linkTypes)
                ->group('cpla.product_link_attribute_code');

            $this->productLinkAttributesCache = $connection->fetchAll($select);
        }

        return $this->productLinkAttributesCache;
    }
}
