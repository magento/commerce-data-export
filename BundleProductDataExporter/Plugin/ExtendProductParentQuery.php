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

namespace Magento\BundleProductDataExporter\Plugin;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\ParentProductDataExporter\Model\Query\ProductParentQuery;

/**
 * Plugin for get parent products query class
 */
class ExtendProductParentQuery
{
    private const BUNDLE_FIXED_TYPE = 'bundle_fixed';

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * MainProductQuery constructor.
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Add bundle product type data to select
     *
     * @param ProductParentQuery $subject
     * @param Select $select
     * @param array $arguments
     * @return Select
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetQuery(ProductParentQuery $subject, Select $select, array $arguments): Select
    {
        $connection = $this->resourceConnection->getConnection();
        $joinField = $connection->getAutoIncrementField(
            $this->resourceConnection->getTableName('catalog_product_entity')
        );

        $select->joinLeft(
            ['eav_attribute' => $this->resourceConnection->getTableName('eav_attribute')],
            'eav_attribute.attribute_code = \'price_type\'',
            []
        )->joinLeft(
            ['eavi_parent' => $this->resourceConnection->getTableName('catalog_product_entity_int')],
            \sprintf('parent_cpe.%1$s = eavi_parent.%1$s', $joinField) .
            ' AND eavi_parent.attribute_id = eav_attribute.attribute_id' .
            ' AND eavi_parent.store_id = 0',
            []
        )->columns(
            [
                'productType' => 'IF(eavi_parent.value = 1, \'' . self::BUNDLE_FIXED_TYPE . '\', cpe.type_id)'
            ]
        );

        return $select;
    }
}
