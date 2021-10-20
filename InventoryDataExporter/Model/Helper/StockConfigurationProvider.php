<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryDataExporter\Model\Helper;

use Magento\CatalogInventory\Api\StockConfigurationInterface as StockConfigurationInterface;

/**
 * Class for getting stock configuration info
 */
class StockConfigurationProvider
{
    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @param StockConfigurationInterface $stockConfiguration
     */
    public function __construct(
        StockConfigurationInterface $stockConfiguration,
    ) {
        $this->stockConfiguration = $stockConfiguration;
    }

    /**
     * Getting manage stock configuration value
     *
     * @return bool
     */
    public function getManageStock(): bool
    {
        return (boolean)$this->stockConfiguration->getManageStock();
    }
}
