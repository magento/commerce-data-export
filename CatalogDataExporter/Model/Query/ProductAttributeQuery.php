<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Query;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\CatalogDataExporter\Model\Query\Eav\EavAttributeQueryBuilderFactory;

/**
 * Product attribute query for catalog data exporter
 */
class ProductAttributeQuery
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var string
     */
    private $mainTable;

    /**
     * @var array
     */
    private array $userDefinedAttributes;

    /**
     * @var EavAttributeQueryBuilderFactory
     */
    private ?EavAttributeQueryBuilderFactory $attributeQueryFactory;

    /**
     * MainProductQuery constructor.
     *
     * @param ResourceConnection $resourceConnection
     * @param string $mainTable
     * @param EavAttributeQueryBuilderFactory|null $attributeQueryFactory
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        string $mainTable = 'catalog_product_entity',
        EavAttributeQueryBuilderFactory $attributeQueryFactory = null
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->mainTable = $mainTable;
        $this->attributeQueryFactory = $attributeQueryFactory
            ?? ObjectManager::getInstance()->get(EavAttributeQueryBuilderFactory::class);
    }

    /**
     * Get resource table
     *
     * @param string $tableName
     * @return string
     */
    private function getTable(string $tableName) : string
    {
        return $this->resourceConnection->getTableName($tableName);
    }

    /**
     * Get user defined attributes ids from EAV
     *
     * @return array
     */
    private function getUserDefinedAttributes() : array
    {
        if (isset($this->userDefinedAttributes)) {
            return $this->userDefinedAttributes;
        }
        $connection = $this->resourceConnection->getConnection();
        $attributes = $connection->fetchCol(
            $connection->select()
                ->from(['a' => $this->getTable('eav_attribute')], [])
                ->join(
                    ['t' => $this->getTable('eav_entity_type')],
                    't.entity_type_id = a.entity_type_id',
                    []
                )
                ->where('a.is_user_defined  = 1')
                ->where('t.entity_table = ?', $this->mainTable)
                ->where('a.backend_type != ?', 'static')
                ->columns(
                    [
                        'code' => 'a.attribute_code',
                    ]
                )
        );
        $this->userDefinedAttributes = array_combine($attributes, $attributes);

        return $this->userDefinedAttributes;
    }

    /**
     * Get query for provider
     *
     * @param array $arguments
     * @return Select|null
     * @throws \Zend_Db_Select_Exception
     */
    public function getQuery(array $arguments) : ?Select
    {
        $productIds = isset($arguments['productId']) ? $arguments['productId'] : [];
        $storeViewCode = isset($arguments['storeViewCode']) ? $arguments['storeViewCode'] : [];

        $userDefinedAttributeIds = $this->getUserDefinedAttributes();
        $attributeQueryBuilder = $this->attributeQueryFactory->create(
            [
                'entityType' => ProductInterface::class,
            ]
        );

        return !empty($userDefinedAttributeIds)
            ? $attributeQueryBuilder->build($productIds, $userDefinedAttributeIds, $storeViewCode)
            : null;
    }
}
