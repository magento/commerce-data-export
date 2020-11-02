<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Currency data provider
 */
class Currency
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Currency constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get provider data
     *
     * @param array $values
     * @return array
     */
    public function get(array $values) : array
    {
        $queryArguments = [
            'storeViewCode' => []
        ];
        // Get unique store view codes
        foreach ($values as $value) {
            $queryArguments['storeViewCode'][$value['storeViewCode']] = $value['storeViewCode'];
        }

        // Get unique currency codes
        $storeCurrencies = [];
        foreach ($queryArguments['storeViewCode'] as $storeViewCode) {
            $storeCurrency = $this->scopeConfig->getValue(
                \Magento\Directory\Model\Currency::XML_PATH_CURRENCY_BASE,
                ScopeInterface::SCOPE_STORE,
                $storeViewCode
            );
            $storeCurrencies[$storeViewCode] = $storeCurrency;
        }

        // Build currency per product/store view code combination
        $productCurrencies = [];
        foreach ($values as $value) {
            $productCurrencies[] = [
                'productId' => $value['productId'],
                'storeViewCode' => $value['storeViewCode'],
                'currency' => $storeCurrencies[$value['storeViewCode']]
            ];
        }

        return $productCurrencies;
    }
}
