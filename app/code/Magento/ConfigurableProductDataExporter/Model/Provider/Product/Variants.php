<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurableProductDataExporter\Model\Provider\Product;

use Magento\ConfigurableProductDataExporter\Model\Query\ProductVariantQuery;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * Configurable product variant data provider
 */
class Variants
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ProductVariantQuery
     */
    private $productVariantQuery;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Variants constructor.
     * @param ResourceConnection $resourceConnection
     * @param ProductVariantQuery $productVariantQuery
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ProductVariantQuery $productVariantQuery,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->productVariantQuery = $productVariantQuery;
        $this->logger = $logger;
    }

    /**
     * Get provider data
     *
     * @param array $values
     * @return array
     * @throws UnableRetrieveData
     */
    public function get(array $values) : array
    {
        $connection = $this->resourceConnection->getConnection();
        $queryArguments = [];
        try {
            $output = [];
            foreach ($values as $value) {
                $queryArguments['productId'][$value['productId']] = $value['productId'];
                $queryArguments['storeViewCode'][$value['storeViewCode']] = $value['storeViewCode'];
            }
            $select = $this->productVariantQuery->getQuery($queryArguments);
            $cursor = $connection->query($select);
            while ($row = $cursor->fetch()) {
                $key = $row['sku'] . '-' . $row['storeViewCode'];
                $output[$key]['variants']['sku'] = $row['sku'];
                $output[$key]['productId'] = $row['productId'];
                $output[$key]['storeViewCode'] = $row['storeViewCode'];
                $output[$key]['variants']['minimumPrice']['regularPrice'] = $row['price'];
                $output[$key]['variants']['minimumPrice']['finalPrice'] = $row['finalPrice'];
                $output[$key]['variants']['selections'][] = [
                    'name' => $row['name'],
                    'value' => $row['value']
                ];
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve product variant data');
        }
        return array_values($output);
    }
}
