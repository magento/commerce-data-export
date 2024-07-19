<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
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
