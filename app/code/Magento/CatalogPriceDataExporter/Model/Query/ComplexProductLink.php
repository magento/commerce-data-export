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
     * @var string
     */
    private $linkIdColumn;

    /**
     * @param ResourceConnection $resourceConnection
     * @param string $linkTable
     * @param string $parentColumn
     * @param string $variationColumn
     * @param string $linkIdColumn
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        string $linkTable,
        string $parentColumn,
        string $variationColumn,
        string $linkIdColumn
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->linkTable = $linkTable;
        $this->parentColumn = $parentColumn;
        $this->variationColumn = $variationColumn;
        $this->linkIdColumn = $linkIdColumn;
    }

    /**
     * Retrieve query for complex product.
     *
     * @param int[]|null $parentIds
     * @param int[]|null $variationIds
     * @param int|null $lastKnownId
     * @param int|null $batchSize
     * @return Select
     */
    public function getQuery(?array $parentIds = [], ?array $variationIds = [], ?int $lastKnownId = 0, ?int $batchSize = null): Select
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
                    'link_id' => \sprintf('link.%s', $this->linkIdColumn),

                ]
            )
            ->where(\sprintf('link.%s > ?', $this->linkIdColumn), $lastKnownId)
            ->order(\sprintf('link.%s', $this->linkIdColumn));

        if (!empty($parentIds)) {
            $select->where('cpe.entity_id in (?)', $parentIds);
        }
        if (!empty($variationIds)) {
            $select->where(\sprintf('link.%s in (?)', $this->variationColumn), $variationIds);
        }
        if (null !== $batchSize) {
            $select->limit($batchSize);
        }
        return $select;
    }
}
