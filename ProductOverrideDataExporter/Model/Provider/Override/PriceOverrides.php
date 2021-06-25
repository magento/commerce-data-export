<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ProductOverrideDataExporter\Model\Provider\Override;

use Magento\QueryXml\Model\QueryProcessor;
use Magento\Customer\Model\Group;
use Magento\Store\Api\Data\WebsiteInterface;

class PriceOverrides
{
    /**
     * @var QueryProcessor
     */
    private $queryProcessor;

    public function __construct(
        QueryProcessor $queryProcessor
    )
    {
        $this->queryProcessor = $queryProcessor;
    }

    private function priceDiffersFromDefault(array $default, array $override): bool
    {
        return (
            $default['prices']['minimumPrice']['finalPrice'] != $override['finalPrice'] ||
            $default['prices']['minimumPrice']['regularPrice'] != $override['regularPrice']
        );
    }

    /**
     * Format data to reflect the message structure
     *
     * @param array $row
     * @return array
     */
    private function format(array $row): array
    {
        $row['prices'] = [
            'minimumPrice' => [
                'finalPrice' => $row['minimumFinalPrice'],
                'regularPrice' => $row['minimumRegularPrice']
            ],
            'maximumPrice' => [
                'finalPrice' => $row['maximumFinalPrice'],
                'regularPrice' => $row['maximumRegularPrice']
            ]
        ];
        return $row;
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
        foreach (['productPriceOverrides', 'configurableProductPriceOverrides'] as $queryName) {
            $cursor = $this->queryProcessor->execute($queryName, $queryArguments);
            while ($row = $cursor->fetch()) {
                $defaultPriceKey = $row['productId'] . WebsiteInterface::ADMIN_CODE . Group::NOT_LOGGED_IN_ID;
                $actualPriceKey = $row['productId'] . $row['websiteCode'] . $row['customerGroupCode'];
                $output[$actualPriceKey] = $row;
                if (!isset($output[$defaultPriceKey]) || $this->priceDiffersFromDefault(
                        $output[$defaultPriceKey],
                        $row
                    )
                ) {
                    $output[$actualPriceKey] = $this->format($row);
                }
            }
        }
        return $output;
    }
}
