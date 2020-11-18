<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ProductOverrideDataExporter\Model\Provider\Override;

use Magento\QueryXml\Model\QueryProcessor;

class PriceOverrides
{
    /**
     * @var QueryProcessor
     */
    private $queryProcessor;

    public function __construct(
        QueryProcessor $queryProcessor
    ) {
        $this->queryProcessor = $queryProcessor;
    }

    /**
     * @param array $values
     * @return array
     */
    public function get(array $values) : array
    {
        foreach ($values as $value) {
            $queryArguments['productId'][$value['productId']] = $value['productId'];
        }
        $output = [];
        $cursor = $this->queryProcessor->execute('productPriceOverrider', $queryArguments);
        for ($row = $cursor->fetch()) {
            $output[] = $row;
        }
    }
}
