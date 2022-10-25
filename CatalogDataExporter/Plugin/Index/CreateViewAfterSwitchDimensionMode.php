<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Plugin\Index;

use Magento\Catalog\Model\Indexer\Product\Price\DimensionModeConfiguration;
use Magento\Catalog\Model\Indexer\Product\Price\ModeSwitcher;
use Magento\CatalogDataExporter\Model\CreatePriceReadTable;
use Magento\Framework\App\ResourceConnection;

/**
 * Create mysql view table for price index after enabled dimension future only in read mode
 */
class CreateViewAfterSwitchDimensionMode
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var SaveNewPriceIndexerMode
     */
    private $createDbView;

    /**
     * @param ResourceConnection $resourceConnection
     * @param CreatePriceReadTable $createDbView
     */
    public function __construct(ResourceConnection $resourceConnection, CreatePriceReadTable $createDbView)
    {
        $this->createDbView = $createDbView;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Recreate price view
     * @param ModeSwitcher $subject
     * @param null $result
     * @param string $currentMode
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSwitchMode(
        ModeSwitcher $subject,
        $result,
        string $currentMode
    ) {
        $connection = $this->resourceConnection->getConnection();
        $viewName = $this->resourceConnection->getTableName('catalog_product_index_price') . '_read';
        if ($currentMode == DimensionModeConfiguration::DIMENSION_NONE) {
            $connection->query("DROP VIEW IF EXISTS $viewName");
            return;
        }
        $this->createDbView->createView($currentMode);

        return $result;
    }
}