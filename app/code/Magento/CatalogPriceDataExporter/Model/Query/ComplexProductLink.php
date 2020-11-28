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
     * @param string $entityId
     * @param string $variationId
     *
     * @return Select
     */
    public function getQuery(string $entityId, string $variationId): Select
    {
        return $this->resourceConnection->getConnection()
            ->select()
            ->from($this->resourceConnection->getTableName($this->linkTable), ['is_active' => new Expression(1)])
            ->where(\sprintf('%s = ?', $this->parentColumn), $entityId)
            ->where(\sprintf('%s = ?', $this->variationColumn), $variationId);
    }
}
