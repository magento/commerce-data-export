<?php
/**
 * Copyright 2024 Adobe
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
        $storeViewItemN = [];
        foreach ($queryArguments as $scopeId => $productData) {
            $cursor = $connection->query(
                $this->productMainQuery->getQuery(\array_keys($productData), $scopeId ?: null)
            );

            while ($row = $cursor->fetch()) {
                $storeViewCode = $row['storeViewCode'];
                $productId = $row['productId'];

                if (!isset($storeViewItemN[$storeViewCode])) {
                    $storeViewItemN[$storeViewCode] = 0;
                }
                $storeViewItemN[$storeViewCode]++;

                $mappedProducts[$storeViewCode][$productId] = $row;
                $attributesData[$storeViewCode][$productId] = $productData[$productId];

                if ($storeViewItemN[$storeViewCode] % $metadata->getBatchSize() == 0
                    || count($mappedProducts) % $metadata->getBatchSize() == 0) {
                    $this->processProducts(
                        ['storeViewCode' => $mappedProducts[$storeViewCode]],
                        ['storeViewCode' => $attributesData[$storeViewCode]],
                        $dataProcessorCallback,
                        $metadata
                    );
                    unset($mappedProducts[$storeViewCode], $attributesData[$storeViewCode]);
                }
            }
        }
        if (empty($storeViewItemN)) {
            $productsIds = \implode(',', \array_unique(\array_column($arguments, 'productId')));
            $scopes = \implode(',', \array_unique(\array_column($arguments, 'scopeId')));
            $this->logger->info(
                \sprintf(
                    'Product exporter: no product data found for ids %s in scopes %s.'
                    . ' Is product deleted or un-assigned from website?',
                    $productsIds,
                    $scopes
                )
            );
        } else {
            $this->processProducts($mappedProducts, $attributesData, $dataProcessorCallback, $metadata);
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
     * @param FeedIndexMetadata $metadata
     * @return void
     * @throws UnableRetrieveData
     */
    private function processProducts(
        array $mappedProducts,
        array $attributesData,
        callable $dataProcessorCallback,
        FeedIndexMetadata $metadata
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
