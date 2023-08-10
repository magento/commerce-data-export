<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Query\Eav;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\DB\Select;

/**
 * Build SQL query for fetch product attributes
 */
class ProductAttributeQueryBuilder implements EavAttributeQueryBuilderInterface
{
    /**
     * List of attributes that need to be added/removed to fetch
     *
     * @var array
     */
    private static $linkedAttributeMap = [
        'small_image' => ['small_image_label', 'name'],
        'image' => ['image_label', 'name'],
        'thumbnail' => ['thumbnail_label', 'name'],
    ];

    /**
     * @var array
     */
    private static $requiredProductAttributes = [
        'entity_id',
        'type_id',
        'sku'
    ];

    /**
     * @var EavAttributeQueryBuilderFactory
     */
    private $attributeQueryFactory;

    /**
     * @param EavAttributeQueryBuilderFactory $attributeQueryFactory
     * @param array $linkedAttributes
     */
    public function __construct(
        EavAttributeQueryBuilderFactory $attributeQueryFactory,
        array $linkedAttributes = []
    ) {
        $this->attributeQueryFactory = $attributeQueryFactory;
        self::$linkedAttributeMap = array_merge(self::$linkedAttributeMap, $linkedAttributes);
    }

    /**
     * @inheritDoc
     */
    public function build(array $entityIds, array $attributes, string $storeCode): Select
    {
        $productAttributes = \array_merge($attributes, self::$requiredProductAttributes);

        $attributeQueryBuilder = $this->attributeQueryFactory->create(
            [
                'entityType' => ProductInterface::class,
                'linkedAttributes' => self::$linkedAttributeMap,
            ]
        );

        return $attributeQueryBuilder->build($entityIds, $productAttributes, $storeCode);
    }
}
