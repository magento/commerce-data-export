<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Plugin\Index;

use Magento\Catalog\Model\Indexer\Product\Price\ModeSwitcherConfiguration;
use Magento\Framework\App\Config\MutableScopeConfigInterface;

/**
 * Create mysql view table for price index after enabled dimension future only in read mode
 */
class SaveNewPriceIndexerMode
{
    /**
     * @var MutableScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param MutableScopeConfigInterface $scopeConfig
     */
    public function __construct(MutableScopeConfigInterface $scopeConfig) {
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
}
