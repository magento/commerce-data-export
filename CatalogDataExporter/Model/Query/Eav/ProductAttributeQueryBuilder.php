<?php
/**
 * Copyright 2023 Adobe
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
