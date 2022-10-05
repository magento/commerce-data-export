<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Plugin\Index;

use Magento\Catalog\Model\Indexer\Product\Price\DimensionCollectionFactory;
use Magento\Catalog\Model\Indexer\Product\Price\DimensionModeConfiguration;
use Magento\Catalog\Model\Indexer\Product\Price\ModeSwitcher;
use Magento\Catalog\Model\Indexer\Product\Price\ModeSwitcherConfiguration;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\Search\Request\IndexScopeResolverInterface as TableResolver;
use Magento\Catalog\Model\Indexer\Product\Price\TableMaintainer;
use Magento\Framework\App\ResourceConnection;

/**
 * Create mysql view table for price index after enabled dimension future only in read mode
 */
class CreatePriceReadTable
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var TableResolver
     */
    private $tableResolver;

    /**
     * @var DimensionCollectionFactory
     */
    private $dimensionCollectionFactory;

    /**
     * @var MutableScopeConfigInterface
     */
    private $scopeConfig;


    /**
     * @param ResourceConnection $resourceConnection
     * @param TableResolver $tableResolver
     * @param DimensionCollectionFactory $dimensionCollectionFactory
     * @param MutableScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        TableResolver $tableResolver,
        DimensionCollectionFactory $dimensionCollectionFactory,
        MutableScopeConfigInterface $scopeConfig
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->tableResolver = $tableResolver;
        $this->dimensionCollectionFactory = $dimensionCollectionFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Save new price mode
     *
     * @param ModeSwitcherConfiguration $subject
     * @param $rusult
     * @param string $mode
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSaveMode(
        ModeSwitcherConfiguration $subject,
        $rusult,
        string $mode
    ) {
        $this->scopeConfig->setValue(
            ModeSwitcherConfiguration::XML_PATH_PRICE_DIMENSIONS_MODE, $mode
        );
        return $rusult;
    }

    /**
     * Recreate price view
     *
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
        $this->createView($currentMode);

        return $result;
    }

    /**
     * Recreate price view
     *
     * @param TableMaintainer $subject
     * @param null $result
     * @param array $dimensions
     * @return null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCreateTablesForDimensions(
        TableMaintainer $subject,
        $result,
        array $dimensions
    ) {
        $mode = $this->scopeConfig->getValue(ModeSwitcherConfiguration::XML_PATH_PRICE_DIMENSIONS_MODE);
        if ($mode !== DimensionModeConfiguration::DIMENSION_NONE) {
            $this->createView($mode);
        }
        return $result;
    }

    /**
     * Recreate price view
     *
     * @param TableMaintainer $subject
     * @param null $result
     * @param array $dimensions
     * @return null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterDropTablesForDimensions(
        TableMaintainer $subject,
        $result,
        array $dimensions
    ) {
        $mode = $this->scopeConfig->getValue(ModeSwitcherConfiguration::XML_PATH_PRICE_DIMENSIONS_MODE);
        if ($mode !== DimensionModeConfiguration::DIMENSION_NONE) {
            $this->createView($mode);
        }
        return $result;
    }

    /**
     * @param string $mode
     * @return void
     */
    private function createView(string $mode): void
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
