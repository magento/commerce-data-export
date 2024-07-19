<?php
/**
 * Copyright 2023 Adobe
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

namespace Magento\BundleProductDataExporter\Model\Provider\Product;

use Magento\QueryXml\Model\QueryProcessor;
use Magento\Catalog\Model\Product\Type as ProductType;

/**
 * Determine bundle product type
 */
class Type
{
    private const BUNDLE_FIXED_TYPE = 'bundle_fixed';

    private QueryProcessor $queryProcessor;

    /**
     * @param QueryProcessor $queryProcessor
     */
    public function __construct(
        QueryProcessor  $queryProcessor
    ) {
        $this->queryProcessor = $queryProcessor;
    }
    
    /**
     * Get provider data
     *
     * @param array $values
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function get(array $values) : array
    {
        $output = [];
        $bundleIds = [];
        foreach ($values as $value) {
            if (!isset($output[$value['sku']])) {
                $output[$value['sku']] = [
                    'type' => $value['type'],
                    'sku' => $value['sku']
                ];
                // Get list of bundle products
                if ($value['type'] === ProductType::TYPE_BUNDLE) {
                    $bundleIds[] = $value['productId'];
                }
            }
        }
        $cursor = $this->queryProcessor->execute('bundleFixedProductType', ['ids' => $bundleIds]);
        while ($row = $cursor->fetch()) {
            // Set bundle_fixed product type if product has price (only bundle fixed products have it)
            if (isset($output[$row['sku']])) {
                $output[$row['sku']]['type'] = self::BUNDLE_FIXED_TYPE;
            }
        }

        return $output;
    }
}
