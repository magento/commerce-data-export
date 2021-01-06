<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\Expression;

/**
 * Complex product variations query provider class
 */
class ComplexProductLink
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var string
     */
    private $linkTable;

    /**
     * @var string
     */
    private $parentColumn;

    /**
     * @var string
     */
    private $variationColumn;

    /**
     * @param ResourceConnection $resourceConnection
     * @param string $linkTable
     * @param string $parentColumn
     * @param string $variationColumn
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        string $linkTable,
        string $parentColumn,
        string $variationColumn
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->linkTable = $linkTable;
        $this->parentColumn = $parentColumn;
        $this->variationColumn = $variationColumn;
    }

    /**
     * Retrieve query for complex product.
     *
     * @param int[] $parentIds
     * @param int[] $variationIds
     *
     * @return Select
     */
    public function getQuery(array $parentIds, array $variationIds = []): Select
    {
        $connection = $this->resourceConnection->getConnection();
        $productEntityTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $joinField = $connection->getAutoIncrementField($productEntityTable);

        $select = $this->resourceConnection->getConnection()
            ->select()
            ->from(['cpe' => $productEntityTable], [])
            ->join(
                ['link' => $this->resourceConnection->getTableName($this->linkTable)],
                \sprintf('cpe.%s = link.%s', $joinField, $this->parentColumn),
                []
            )
            ->columns(
                [
                    'parent_id' => 'cpe.entity_id',
                    'variation_id' => \sprintf('link.%s', $this->variationColumn),
                ]
            )
            ->where('cpe.entity_id in (?)', $parentIds);

        if (!empty($variationIds)) {
            $select->where(\sprintf('link.%s in (?)', $this->variationColumn), $variationIds);
        }

        return $select;
    }
}
