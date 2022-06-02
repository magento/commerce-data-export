<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurableProductDataExporter\Model\Provider\Product;

use Exception;
use Generator;
use Magento\CatalogDataExporter\Model\Provider\Product\OptionProviderInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProductDataExporter\Model\Query\ProductOptionQuery;
use Magento\ConfigurableProductDataExporter\Model\Query\ProductOptionValueQuery;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Query\BatchIteratorInterface;
use Magento\Framework\DB\Query\Generator as QueryGenerator;
use Magento\Framework\DB\Select;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;
use Throwable;

/**
 * Configurable product options data provider
 */
class Options implements OptionProviderInterface
{
    /**
     * Batch sizing for performing queries
     *
     * @var int
     */
    private $batchSize;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ProductOptionQuery
     */
    private $productOptionQuery;

    /**
     * @var ProductOptionValueQuery
     */
    private $productOptionValueQuery;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var QueryGenerator
     */
    private $queryGenerator;

    /**
     * @var ConfigurableOptionValueUid
     */
    private $optionValueUid;

    private static $optionValuesPerAttributesCache = [];

    /**
     * @param ResourceConnection $resourceConnection
     * @param ProductOptionQuery $productOptionQuery
     * @param ProductOptionValueQuery $productOptionValueQuery
     * @param QueryGenerator $queryGenerator
     * @param ConfigurableOptionValueUid $optionValueUid
     * @param LoggerInterface $logger
     * @param int $batchSize
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ProductOptionQuery $productOptionQuery,
        ProductOptionValueQuery $productOptionValueQuery,
        QueryGenerator $queryGenerator,
        ConfigurableOptionValueUid $optionValueUid,
        LoggerInterface $logger,
        int $batchSize
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->productOptionQuery = $productOptionQuery;
        $this->productOptionValueQuery = $productOptionValueQuery;
        $this->queryGenerator = $queryGenerator;
        $this->optionValueUid = $optionValueUid;
        $this->logger = $logger;
        $this->batchSize = $batchSize;
    }

    /**
     * Retrieve query data in batches
     *
     * @param Select $select
     * @param string $rangeField
     * @return Generator
     * @throws UnableRetrieveData
     */
    private function getBatchedQueryData(Select $select, string $rangeField): Generator
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $iterator = $this->queryGenerator->generate(
                $rangeField,
                $select,
                $this->batchSize,
                BatchIteratorInterface::NON_UNIQUE_FIELD_ITERATOR
            );
            foreach ($iterator as $batchSelect) {
                yield $connection->fetchAll($batchSelect);
            }
        } catch (Throwable $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            throw new UnableRetrieveData('Unable to retrieve configurable option data');
        }
    }

    /**
     * Get option values
     *
     * @param array $arguments
     * @return array
     * @throws UnableRetrieveData
     */
    private function getOptionValuesData(array $arguments): array
    {
        $attributeIdsOrigin = $this->getAttributeIds($arguments);
        $attributeIds = \array_diff(
            $attributeIdsOrigin,
            \array_keys(self::$optionValuesPerAttributesCache)
        );

        if (!$attributeIds) {
            // get from cache
            return $this->getOptionValuesFromCache($attributeIdsOrigin);
        }
        $arguments['attributes'] = $attributeIds;

        $select = $this->productOptionValueQuery->getQuery($arguments);
        // ad hoc solution to check application cache size. should be replaced with generic approach
        $cacheSize = \strlen(\json_encode(self::$optionValuesPerAttributesCache));
        if ($cacheSize > 1024 * 1024 * 20) {
            self::$optionValuesPerAttributesCache = [];
        }
        foreach ($this->getBatchedQueryData($select, 'attribute_id') as $batchData) {
            foreach ($batchData as $row) {
                self::$optionValuesPerAttributesCache[$row['attribute_id']][$row['storeViewCode']][$row['optionId']] = [
                    'id' => $this->optionValueUid->resolve($row['attribute_id'], $row['optionId']),
                    'label' => $row['label'],
                ];
            }
        }

        return $this->getOptionValuesFromCache($attributeIdsOrigin);
    }

    /**
     * @param array $arguments
     * @return array
     */
    private function getAttributeIds(array $arguments): array
    {
        $productIds = $arguments['productId'] ?? [];
        $connection = $this->resourceConnection->getConnection();
        $joinField = $connection->getAutoIncrementField(
            $this->resourceConnection->getTableName('catalog_product_entity')
        );
        $subSelect = $connection->select()
            ->from(
                ['cpe' => $this->resourceConnection->getTableName('catalog_product_entity')],
                []
            )
            ->join(
                ['psa' => $this->resourceConnection->getTableName('catalog_product_super_attribute')],
                sprintf('psa.product_id = cpe.%s', $joinField),
                ['attribute_id' => 'psa.attribute_id']
            )
            ->where('cpe.entity_id IN (?)', $productIds)
            ->distinct(true);
        $connection = $this->resourceConnection->getConnection();
        return $connection->fetchCol($subSelect);
    }

    /**
     * Format options row in appropriate format for feed data storage
     *
     * @param array $row
     * @return array
     */
    private function formatOptionsRow($row): array
    {
        return [
            'productId' => $row['productId'],
            'storeViewCode' => $row['storeViewCode'],
            'optionsV2' => [
                'id' => $row['attribute_code'],
                'type' => ConfigurableOptionValueUid::OPTION_TYPE,
                'label' => $row['label'],
                'sortOrder' => $row['position']
            ],
        ];
    }

    /**
     * Generate option key by concatenating productId, storeViewCode and attributeId
     *
     * @param array $row
     * @return string
     */
    private function getOptionKey($row): string
    {
        return $row['productId'] . $row['storeViewCode'] . $row['attribute_id'];
    }

    /**
     * @inheritDoc
     */
    public function get(array $values): array
    {

        $queryArguments = [];
        foreach ($values as $value) {
            if (!isset($value['productId'], $value['type'], $value['storeViewCode'])
                || $value['type'] !== Configurable::TYPE_CODE ) {
                continue;
            }
            $queryArguments['productId'][$value['productId']] = $value['productId'];
            $queryArguments['storeViewCode'][$value['storeViewCode']] = $value['storeViewCode'];
        }

        if (!$queryArguments) {
            return [];
        }
        try {
            $options = [];
            $optionValuesData = $this->getOptionValuesData($queryArguments);

            $select = $this->productOptionQuery->getQuery($queryArguments);
            foreach ($this->getBatchedQueryData($select, 'entity_id') as $batchData) {
                foreach ($batchData as $row) {
                    $key = $this->getOptionKey($row);
                    $options[$key] = $options[$key] ?? $this->formatOptionsRow($row);

                    if (isset($optionValuesData[$row['attribute_id']][$row['storeViewCode']])) {
                        $options[$key]['optionsV2']['values'] = $this->getAssignedAttributeValues(
                            $optionValuesData[$row['attribute_id']][$row['storeViewCode']],
                            explode(',', $row['attributeValues'])
                        );
                    }
                }
            }
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            throw new UnableRetrieveData('Unable to retrieve configurable product options data');
        }
        return $options;
    }

    /**
     * @param array|null $attributeIds
     * @return array
     */
    private function getOptionValuesFromCache(?array $attributeIds): array
    {
        return \array_intersect_key(self::$optionValuesPerAttributesCache, \array_flip($attributeIds));
    }

    /**
     * @param array $attributeValuesList
     * @param array $assignedAttributeValuesId
     * @return array
     */
    private function getAssignedAttributeValues(array $attributeValuesList, array $assignedAttributeValuesId): array
    {
        $assignedAttributeValues = array_intersect_key($attributeValuesList, array_flip($assignedAttributeValuesId));

        return !empty($assignedAttributeValues) ? \array_values($assignedAttributeValues) : [];
    }
}
