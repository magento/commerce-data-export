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

use Magento\CatalogDataExporter\Model\Provider\Category\Formatter\FormatterInterface;
use Magento\CatalogDataExporter\Model\Provider\EavAttributes\EntityEavAttributesResolver;
use Magento\CatalogDataExporter\Model\Query\CategoryMainQuery;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\DataExporter\Export\DataProcessorInterface;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\Store;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * Categories main data provider
 */
class Categories implements DataProcessorInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var CategoryMainQuery
     */
    private $categoryMainQuery;

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
     * @param ResourceConnection $resourceConnection
     * @param CategoryMainQuery $categoryMainQuery
     * @param FormatterInterface $formatter
     * @param LoggerInterface $logger
     * @param EntityEavAttributesResolver $entityEavAttributesResolver
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        CategoryMainQuery $categoryMainQuery,
        FormatterInterface $formatter,
        LoggerInterface $logger,
        EntityEavAttributesResolver $entityEavAttributesResolver
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->categoryMainQuery = $categoryMainQuery;
        $this->formatter = $formatter;
        $this->logger = $logger;
        $this->entityEavAttributesResolver = $entityEavAttributesResolver;
    }

    /**
     * @inheritdoc
     *
     * @throws UnableRetrieveData
     * @throws \Zend_Db_Statement_Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(
        array $arguments,
        callable $dataProcessorCallback,
        FeedIndexMetadata $metadata,
        $node = null,
        $info = null,
        $lastChunk = null
    ): void {
        try {
            foreach ($this->getDataBatch($arguments, $metadata->getBatchSize()) as $dataBatch) {
                $output = [];
                list($mappedCategories, $attributesData) = $dataBatch;
                foreach ($mappedCategories as $storeCode => $categories) {
                    $output[] = \array_map(function ($row) {
                        return $this->formatter->format($row);
                    }, \array_replace_recursive(
                        $categories,
                        $this->entityEavAttributesResolver->resolve($attributesData[$storeCode], $storeCode)
                    ));
                }
                $dataProcessorCallback($this->get(\array_merge(...$output)));
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            throw new UnableRetrieveData('Unable to retrieve category data');
        }
    }

    /**
     * For backward compatibility - to allow 3rd party plugins work
     *
     * @param array $values
     * @return array
     * @deprecated
     * @see self::execute
     */
    public function get(array $values) : array
    {
        return $values;
    }

    /**
     * Returns data batch.
     *
     * @param array $arguments
     * @param int $batchSize
     * @return \Generator
     * @throws \Zend_Db_Statement_Exception
     */
    private function getDataBatch(array $arguments, int $batchSize): \Generator
    {
        $itemN = 0;
        $queryArguments = [];
        $mappedCategories = [];
        $attributesData = [];
        foreach ($arguments as $value) {
            $scope = $value['scopeId'] ?? Store::DEFAULT_STORE_ID;
            $queryArguments[$scope][$value['categoryId']] = $value['attribute_ids'] ?? [];
        }

        $connection = $this->resourceConnection->getConnection();
        foreach ($queryArguments as $scopeId => $categoryData) {
            $cursor = $connection->query(
                $this->categoryMainQuery->getQuery(\array_keys($categoryData), $scopeId ?: null)
            );

            while ($row = $cursor->fetch()) {
                $itemN++;
                $mappedCategories[$row['storeViewCode']][$row['categoryId']] = $row;
                $attributesData[$row['storeViewCode']][$row['categoryId']] = $categoryData[$row['categoryId']];
                if ($itemN % $batchSize == 0) {
                    yield [$mappedCategories,  $attributesData];
                    $mappedCategories = [];
                    $attributesData = [];
                }
            }
        }

        yield [$mappedCategories, $attributesData];
    }
}
