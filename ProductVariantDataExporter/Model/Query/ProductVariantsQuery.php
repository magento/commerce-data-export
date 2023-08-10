<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Model\Query;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;

/**
 * Build Select object to fetch configurable product variant option values
 */
class ProductVariantsQuery
{
    private const STATUS_ATTRIBUTE_CODE = "status";

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    private Config $eavConfig;

    /**
     * @param ResourceConnection $resourceConnection
     * @param Config $eavConfig
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        Config $eavConfig
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->eavConfig = $eavConfig;
    }

    /**
     * Get query for provider
     *
     * @param array $parentIds
     * @return Select
     * @throws LocalizedException
     */
    public function getQuery(array $parentIds): Select
    {
        $connection = $this->resourceConnection->getConnection();
        $joinField = $connection->getAutoIncrementField(
            $this->resourceConnection->getTableName('catalog_product_entity')
        );

        $statusAttribute = $this->eavConfig->getAttribute('catalog_product', self::STATUS_ATTRIBUTE_CODE);
        $statusAttributeId = $statusAttribute?->getId();

        $subSelect = $connection->select()
            ->from(
                ['cpsa' => $this->resourceConnection->getTableName('catalog_product_super_attribute')],
                ['attribute_id']
            )
            ->where(\sprintf('cpsa.product_id IN (cpep.%s)', $joinField));
        $select = $connection->select()
            ->from(
                ['product' => $this->resourceConnection->getTableName('catalog_product_entity')],
                []
            )
            ->joinInner(
                ['cpsl' => $this->resourceConnection->getTableName('catalog_product_super_link')],
                \sprintf('cpsl.product_id = product.entity_id'),
                []
            )
            ->joinInner(
                ['cpep' => $this->resourceConnection->getTableName('catalog_product_entity')],
                \sprintf('cpep.%s = cpsl.parent_id', $joinField),
                []
            )
            ->joinInner(
                ['cpei' => $this->resourceConnection->getTableName(['catalog_product_entity', 'int'])],
                \sprintf(
                    'cpei.%1$s = product.%1$s AND cpei.attribute_id IN (%2$s)',
                    $joinField,
                    $subSelect->assemble()
                ),
                []
            )
            ->joinInner(
                ['ea' => $this->resourceConnection->getTableName('eav_attribute')],
                'ea.attribute_id = cpei.attribute_id',
                []
            )
            ->joinLeft(
                ['option_value' => $this->resourceConnection->getTableName('eav_attribute_option_value')],
                'option_value.option_id = cpei.value',
                []
            )
            ->joinLeft(
                ['product_status' => $this->resourceConnection->getTableName('catalog_product_entity_int')],
                \sprintf(
                    'product_status.%s = product.%s 
                    AND product_status.attribute_id = %s
                    AND product_status.store_id = 0',
                    $joinField,
                    $joinField,
                    $statusAttributeId,
                ),
                []
            )
            ->columns(
                [
                    'parentId' => 'cpep.entity_id',
                    'childId' => 'product.entity_id',
                    'attributeId' => 'cpei.attribute_id',
                    'attributeCode' => 'ea.attribute_code',
                    'optionValueId' => 'cpei.value',
                    'productSku' => 'product.sku',
                    'parentSku' => 'cpep.sku',
                    'optionLabel' => 'option_value.value'
                ]
            )
            ->where('product.entity_id IN (?)', $parentIds)
            ->where('product_status.value = ?', Status::STATUS_ENABLED);

        return $select;
    }
}
