<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductPriceDataExporter\Model\Query;

use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Get raw price for customer group price
 */
class CustomerGroupPricesQuery
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

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
     * @inheritDoc
     * @throws UnableRetrieveData
     */
    public function getQuery(array $productIds): Select
    {
        /** @var \Magento\Framework\EntityManager\EntityMetadataInterface $metadata */
        $metadata = $this->metadataPool->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class);
        $connection = $this->resourceConnection->getConnection();

        $linkField = $metadata->getLinkField();

        return $connection->select()
            ->from(
                ['product' => $this->resourceConnection->getTableName('catalog_product_entity')],
                [
                    'sku',
                    'entity_id',
                    'type_id',
                ]
            )
            // select "website_id" since tier_price may have "all websites = 0" value
            ->joinInner(
                ['product_website' => $this->resourceConnection->getTableName('catalog_product_website')],
                'product_website.product_id = product.entity_id',
                ['website_id']
            )
            ->joinInner(
                ['tier' => $this->resourceConnection->getTableName('catalog_product_entity_tier_price')],
                \sprintf('product.%1$s = tier.%1$s', $linkField) .
                ' AND tier.qty=1 AND tier.website_id in (0, product_website.website_id)',
                [
                    'all_groups',
                    'value',
                    'percentage_value',
                    'customer_group_id',
                ]
            )
            ->where('product.entity_id IN (?)', $productIds);
    }
}
