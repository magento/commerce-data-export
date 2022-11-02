<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Setup\Patch\Schema;

use Magento\Catalog\Model\Indexer\Product\Price\DimensionModeConfiguration;
use Magento\Catalog\Model\Indexer\Product\Price\ModeSwitcherConfiguration;
use Magento\CatalogDataExporter\Model\CreatePriceReadTable;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\Patch\PatchInterface;

/**
 * Make read table for dimension
 */
class MakeReadTableForDimension implements SchemaPatchInterface
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
     * @inheritDoc
     */
    public function apply(): PatchInterface
    {
        $mode = $this->scopeConfig->getValue(ModeSwitcherConfiguration::XML_PATH_PRICE_DIMENSIONS_MODE);
        if ($mode !== DimensionModeConfiguration::DIMENSION_NONE) {
            $this->createDbView->createView($mode);
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
