<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogInventoryDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Gets information about product inventory
 */
interface CatalogInventoryStockQueryInterface
{
    /**
     * Get query with information about in_stock status
     *
     * @param array $arguments
     * @return Select|null
     */
    public function getInStock(array $arguments) : ?Select;
}
