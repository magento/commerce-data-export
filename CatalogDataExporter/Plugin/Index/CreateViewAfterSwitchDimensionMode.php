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
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Create mysql view table for price index after enabled dimension future only in read mode
 */
class CreateViewAfterSwitchDimensionMode
{
    private ResourceConnection $resourceConnection;
    private CreatePriceReadTable $createDbView;
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param CreatePriceReadTable $createDbView
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        CreatePriceReadTable $createDbView,
        CommerceDataExportLoggerInterface $logger
    ) {
        $this->createDbView = $createDbView;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
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
        try {
            $connection = $this->resourceConnection->getConnection();
            $viewName = $this->resourceConnection->getTableName('catalog_product_index_price') . '_read';
            if ($currentMode === DimensionModeConfiguration::DIMENSION_NONE) {
                $connection->query("DROP VIEW IF EXISTS $viewName");
                return;
            }
            $this->createDbView->createView($currentMode);

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error(
                'Data Exporter exception has occurred: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
