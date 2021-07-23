<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ProductOverrideDataExporter\Model\Provider\Override;

use Magento\QueryXml\Model\QueryProcessor;

class CategoryPermissions
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
        $cursor = $this->queryProcessor->execute('productCategoryPermissions', $queryArguments);
        while ($row = $cursor->fetch()) {
            $key = $row['productId'] . $row['websiteCode'] . $row['customerGroupCode'];
            $output[$key]['productId'] = $row['productId'];
            $output[$key]['websiteCode'] = $row['websiteCode'];
            $output[$key]['customerGroupCode'] = $row['customerGroupCode'];
            $output[$key]['displayable'] = isset($row['displayable']) && $row['displayable'] == -1;
            $output[$key]['buyable'] = isset($row['buyable']) && $row['buyable'] == -1;
            $output[$key]['canDisplayPrice'] = isset($row['canDisplayPrice']) && $row['canDisplayPrice'] == -1;
        }
        return $output;
    }
}
