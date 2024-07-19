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

namespace Magento\CatalogDataExporter\Model\Query\Eav;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\DB\Select;

/**
 * Build SQL query for fetch category attributes
 */
class CategoryAttributeQueryBuilder implements EavAttributeQueryBuilderInterface
{
    /**
     * @var EavAttributeQueryBuilderFactory
     */
    private $attributeQueryFactory;

    /**
     * @var array
     */
    private static $requiredAttributes = [
        'entity_id',
    ];

    /**
     * @param EavAttributeQueryBuilderFactory $attributeQueryFactory
     */
    public function __construct(
        EavAttributeQueryBuilderFactory $attributeQueryFactory
    ) {
        $this->attributeQueryFactory = $attributeQueryFactory;
    }

    /**
     * @inheritDoc
     */
    public function build(array $entityIds, array $attributes, string $storeCode): Select
    {
        $categoryAttributes = \array_merge($attributes, self::$requiredAttributes);

        $attributeQueryBuilder = $this->attributeQueryFactory->create(
            [
                'entityType' => CategoryInterface::class,
            ]
        );

        return $attributeQueryBuilder->build($entityIds, $categoryAttributes, $storeCode);
    }
}
