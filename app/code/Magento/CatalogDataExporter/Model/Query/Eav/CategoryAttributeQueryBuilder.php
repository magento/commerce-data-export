<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
