<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model;

use Magento\Catalog\Model\Indexer\Product\Price\DimensionCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Search\Request\IndexScopeResolverInterface as TableResolver;

class CreatePriceReadTable
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var DimensionCollectionFactory
     */
    private $dimensionCollectionFactory;

    /**
     * @var TableResolver
     */
    private $tableResolver;

    /**
     * @param ResourceConnection $resourceConnection
     * @param DimensionCollectionFactory $dimensionCollectionFactory
     * @param TableResolver $tableResolver
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        DimensionCollectionFactory $dimensionCollectionFactory,
        TableResolver $tableResolver
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->dimensionCollectionFactory = $dimensionCollectionFactory;
        $this->tableResolver = $tableResolver;
    }

    /**
     * @param string $mode
     * @return void
     */
    public function createView(string $mode): void
    {
        $connection = $this->resourceConnection->getConnection();
        $priceName = $this->resourceConnection->getTableName('catalog_product_index_price');
        $viewName = $priceName . '_read';

        $sql = "CREATE OR REPLACE ALGORITHM = MERGE VIEW $viewName AS SELECT * FROM $priceName";
        foreach ($this->dimensionCollectionFactory->create($mode) as $dimensions) {
            $dimensionTableName = $this->tableResolver->resolve('catalog_product_index_price', $dimensions);
            $sql .= " UNION SELECT * FROM $dimensionTableName";
        }

        $connection->query($sql);
    }
}