<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider;

use Magento\CatalogDataExporter\Model\Provider\EavAttributes\EntityEavAttributesResolver;
use Magento\CatalogDataExporter\Model\Provider\Product\Formatter\FormatterInterface;
use Magento\CatalogDataExporter\Model\Query\ProductMainQuery;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\DataExporter\Export\DataProcessorInterface;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Store\Model\Store;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Products data provider
 */
class Products implements DataProcessorInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ProductMainQuery
     */
    private $productMainQuery;

    /**
     * @var FormatterInterface
     */
    private $formatter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EntityEavAttributesResolver
     */
    private $entityEavAttributesResolver;

    /**
     * @var array required attributes for product export
     */
    private array $requiredAttributes;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ProductMainQuery $productMainQuery
     * @param FormatterInterface $formatter
     * @param LoggerInterface $logger
     * @param EntityEavAttributesResolver $entityEavAttributesResolver
     * @param array $requiredAttributes
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ProductMainQuery $productMainQuery,
        FormatterInterface $formatter,
        LoggerInterface $logger,
        EntityEavAttributesResolver $entityEavAttributesResolver,
        array $requiredAttributes = []
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->productMainQuery = $productMainQuery;
        $this->formatter = $formatter;
        $this->logger = $logger;
        $this->entityEavAttributesResolver = $entityEavAttributesResolver;
        $this->requiredAttributes = $requiredAttributes;
    }

    /**
     * Get provider data
     *
     * @param array $arguments
     * @param callable $dataProcessorCallback
     * @param FeedIndexMetadata $metadata
     * @param ? $node
     * @param ? $info
     * @return void
     * @throws UnableRetrieveData
     * @throws \Zend_Db_Statement_Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(
        array $arguments,
        callable $dataProcessorCallback,
        FeedIndexMetadata $metadata,
        $node = null,
        $info = null
    ): void {
        $queryArguments = [];
        $mappedProducts = [];
        $attributesData = [];

        foreach ($arguments as $value) {
            $scope = $value['scopeId'] ?? Store::DEFAULT_STORE_ID;
            $queryArguments[$scope][$value['productId']] = $value['attribute_ids'] ?? [];
        }

        $connection = $this->resourceConnection->getConnection();
        $itemN = 0;
        foreach ($queryArguments as $scopeId => $productData) {
            $cursor = $connection->query(
                $this->productMainQuery->getQuery(\array_keys($productData), $scopeId ?: null)
            );

            while ($row = $cursor->fetch()) {
                $itemN++;
                $mappedProducts[$row['storeViewCode']][$row['productId']] = $row;
                $attributesData[$row['storeViewCode']][$row['productId']] = $productData[$row['productId']];
                if ($itemN % $metadata->getBatchSize() == 0) {
                    $this->processProducts($mappedProducts, $attributesData, $dataProcessorCallback);
                    $mappedProducts = [];
                    $attributesData = [];
                }
            }
        }
        if ($itemN === 0) {
            $productsIds = \implode(',', \array_unique(\array_column($arguments, 'productId')));
            $scopes = \implode(',', \array_unique(\array_column($arguments, 'scopeId')));
            $this->logger->info(
                \sprintf('Product exporter: no product data found for ids %s in scopes %s', $productsIds, $scopes)
            );
        } else {
            $this->processProducts($mappedProducts, $attributesData, $dataProcessorCallback);
        }
    }

    /**
     * For backward compatibility - to allow 3rd party plugins work
     *
     * @param array $values
     * @return array
     */
    public function get(array $values) : array
    {
        return $values;
    }

    /**
     * Process products data
     *
     * @param array $mappedProducts
     * @param array $attributesData
     * @param callable $dataProcessorCallback
     * @return void
     * @throws UnableRetrieveData
     */
    private function processProducts(
        array $mappedProducts,
        array $attributesData,
        callable $dataProcessorCallback
    ): void {
        $output = [];
        foreach ($mappedProducts as $storeCode => $products) {
            $output[] = \array_map(function ($row) {
                return $this->formatter->format($row);
            }, \array_replace_recursive(
                $products,
                $this->entityEavAttributesResolver->resolve($attributesData[$storeCode], $storeCode)
            ));
        }

        $errorEntityIds = [];
        foreach ($output as $part) {
            foreach ($part as $entityId => $attributes) {
                if (array_diff($this->requiredAttributes, array_keys(array_filter($attributes)))) {
                    $errorEntityIds[] = $entityId;
                }
            }
        }
        if (!empty($errorEntityIds)) {
            $this->logger->warning(
                'One or more required EAV attributes ('
                . implode(',', $this->requiredAttributes)
                . ') are not set for products: ' . implode(',', $errorEntityIds)
            );
        }

        $dataProcessorCallback($this->get(\array_merge(...$output)));
    }
}
