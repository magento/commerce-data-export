<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
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
     * @var array
     */
    private array $systemAttributes;

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
     * @param array $systemAttributes
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        string $mainTable = 'catalog_product_entity',
        ?EavAttributeQueryBuilderFactory $attributeQueryFactory = null,
        array $systemAttributes = []
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->mainTable = $mainTable;
        $this->attributeQueryFactory = $attributeQueryFactory
            ?? ObjectManager::getInstance()->get(EavAttributeQueryBuilderFactory::class);
        $this->systemAttributes = $systemAttributes;
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
        $productIds = $arguments['productId'] ?? [];
        $storeViewCode = $arguments['storeViewCode'] ?? [];

        $attributesToSearch = array_merge(
            $this->getUserDefinedAttributes(),
            $this->getSystemAttributes()
        );
        $attributeQueryBuilder = $this->attributeQueryFactory->create(
            [
                'entityType' => ProductInterface::class,
            ]
        );

        return !empty($attributesToSearch)
            ? $attributeQueryBuilder->build($productIds, $attributesToSearch, $storeViewCode)
            : null;
    }

    /**
     * Get system EAV attributes codes
     *
     * @return array
     */
    private function getSystemAttributes(): array
    {
        return $this->systemAttributes;
    }
}
