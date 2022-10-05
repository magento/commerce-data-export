<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Resolver;

use Magento\Catalog\Model\Indexer\Product\Price\DimensionModeConfiguration;
use Magento\Framework\App\ResourceConnection;

/**
 * Class resolve table name for price dimension
 */
class PriceTableResolver
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var DimensionModeConfiguration
     */
    private $dimensionModeConfiguration;

    /**
     * @param ResourceConnection $resourceConnection
     * @param DimensionModeConfiguration $dimensionModeConfiguration
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        DimensionModeConfiguration $dimensionModeConfiguration
    )
    {
        $this->resourceConnection = $resourceConnection;
        $this->dimensionModeConfiguration = $dimensionModeConfiguration;
    }

    /**
     * Resolve price table name for dimension
     * @param $tableName
     * @return string
     */
    public function getTableName($tableName): string
    {
        $realTableName = $this->resourceConnection->getTableName($tableName);
        if ($tableName === 'catalog_product_index_price'
            && $this->dimensionModeConfiguration->getDimensionConfiguration()
        ) {
            return $realTableName . '_read';
        }

        return $realTableName;

    }
}
