<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ProductOverrideDataExporter\Model\Provider\Override\CategoryPermissions;

use Magento\QueryXml\Model\QueryProcessor;

class CanAddToCart
{
    /**
     * @var QueryProcessor
     */
    private $queryProcessor;

    /**
     * @param QueryProcessor $queryProcessor
     */
    public function __construct(
        QueryProcessor $queryProcessor
    )
    {
        $this->queryProcessor = $queryProcessor;
    }

    /**
     * @param array $values
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function get(array $values): array
    {
        foreach ($values as $value) {
            $queryArguments['entityIds'][] = $value['productId'];
        }
        $output = [];
        $cursor = $this->queryProcessor->execute('productCategoryPermissionsCanAddToCart', $queryArguments);
        while ($row = $cursor->fetch()) {
            $key = $row['productId'] . $row['websiteCode'] . $row['customerGroupCode'];
            $output[$key]['productId'] = $row['productId'];
            $output[$key]['websiteCode'] = $row['websiteCode'];
            $output[$key]['customerGroupCode'] = $row['customerGroupCode'];
            $output[$key]['categoryPermissions']['canAddToCart'][] = $row['category'];
        }
        return $output;
    }
}
