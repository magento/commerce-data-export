<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Plugin\Index;

use Magento\Catalog\Model\Indexer\Product\Price\DimensionModeConfiguration;
use Magento\Catalog\Model\Indexer\Product\Price\ModeSwitcherConfiguration;
use Magento\Catalog\Model\Indexer\Product\Price\TableMaintainer;
use Magento\CatalogDataExporter\Model\CreatePriceReadTable;
use Magento\Framework\App\Config\MutableScopeConfigInterface;

/**
 * Create mysql view table for price index after enabled dimension future only in read mode
 */
class CreateViewAfterTableMaintenance
{
    /**
     * @var MutableScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var CreatePriceReadTable
     */
    private $createDbView;

    /**
     * @param MutableScopeConfigInterface $scopeConfig
     * @param CreatePriceReadTable $createDbView
     */
    public function __construct(MutableScopeConfigInterface $scopeConfig, CreatePriceReadTable $createDbView)
    {
        $this->scopeConfig = $scopeConfig;
        $this->createDbView = $createDbView;
    }

    /**
     * Recreate price view
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
            $this->createDbView->createView($mode);
        }
        return $result;
    }

    /**
     * Recreate price view
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
            $this->createDbView->createView($mode);
        }
        return $result;
    }
}