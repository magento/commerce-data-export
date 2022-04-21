<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurableProductDataExporter\Model\Provider\Product;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProductDataExporter\Model\Query\VariantsQuery;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * Configurable product variant data provider
 *  TODO: Deprecated - remove this class and its query. https://github.com/magento/catalog-storefront/issues/419
 */
class Variants
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var VariantsQuery
     */
    private $variantQuery;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Variants constructor.
     * @param ResourceConnection $resourceConnection
     * @param VariantsQuery $variantQuery
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        VariantsQuery $variantQuery,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->variantQuery = $variantQuery;
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
                if (!isset($value['productId'], $value['type'], $value['storeViewCode'])
                    || $value['type'] !== Configurable::TYPE_CODE ) {
                    continue;
                }
                $queryArguments['productId'][$value['productId']] = $value['productId'];
                $queryArguments['storeViewCode'][$value['storeViewCode']] = $value['storeViewCode'];
            }

            if (!$queryArguments) {
                return $output;
            }
            $select = $this->variantQuery->getQuery($queryArguments);
            $cursor = $connection->query($select);
            while ($row = $cursor->fetch()) {
                $key = $row['sku'] . '-' . $row['storeViewCode'];
                $output[$key]['variants']['sku'] = $row['sku'];
                $output[$key]['productId'] = $row['productId'];
                $output[$key]['storeViewCode'] = $row['storeViewCode'];
                $output[$key]['variants']['minimumPrice']['regularPrice'] = $row['price'];
                $output[$key]['variants']['minimumPrice']['finalPrice'] = $row['finalPrice'];
                // Product.Variants are deprecated. Variant.Selections not used anymore
                $output[$key]['variants']['selections'] = [];
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            throw new UnableRetrieveData('Unable to retrieve product variant data');
        }
        return array_values($output);
    }
}
