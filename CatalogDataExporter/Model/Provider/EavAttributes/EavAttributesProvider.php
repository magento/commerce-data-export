<?php
/**
 * Copyright 2022 Adobe
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

namespace Magento\CatalogDataExporter\Model\Provider\EavAttributes;

use Magento\CatalogDataExporter\Model\Query\Eav\EavAttributeQueryBuilderInterface;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\DataExporter\Sql\FieldToPropertyNameConverter;
use Magento\Framework\App\ResourceConnection;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;
use Magento\Store\Model\Store;

/**
 * Eav attributes data provider
 */
class EavAttributesProvider
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var AttributesDataConverter
     */
    private $attributesDataConverter;

    /**
     * @var FieldToPropertyNameConverter
     */
    private $nameConverter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EavAttributeQueryBuilderInterface
     */
    private $queryBuilder;

    /**
     * @var array
     */
    private $includeAttributes;

    /**
     * @param AttributesDataConverter $attributesDataConverter
     * @param ResourceConnection $resourceConnection
     * @param FieldToPropertyNameConverter $nameConverter
     * @param LoggerInterface $logger
     * @param EavAttributeQueryBuilderInterface $queryBuilder
     * @param array $includeAttributes
     */
    public function __construct(
        AttributesDataConverter $attributesDataConverter,
        ResourceConnection $resourceConnection,
        FieldToPropertyNameConverter $nameConverter,
        LoggerInterface $logger,
        EavAttributeQueryBuilderInterface $queryBuilder,
        array $includeAttributes = []
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->attributesDataConverter = $attributesDataConverter;
        $this->nameConverter = $nameConverter;
        $this->logger = $logger;
        $this->queryBuilder = $queryBuilder;
        $this->includeAttributes = $includeAttributes;
    }

    /**
     * Get converted eav attributes data
     *
     * @param int[] $entityIds
     * @param string $storeCode
     * @param string[] $attributeCodes
     *
     * @return array
     *
     * @throws UnableRetrieveData
     */
    public function getEavAttributesData(array $entityIds, string $storeCode, array $attributeCodes = []) : array
    {
        try {
            $attributeCodes = $attributeCodes ?: $this->includeAttributes;

            $attributes = $this->resourceConnection->getConnection()->fetchAll(
                $this->queryBuilder->build($entityIds, $attributeCodes, $storeCode)
            );
            $attributesPerEntity = $this->attributesDataConverter->convert($attributes);

            // covers edge-case when export started before EAV attributes has been fulfilled
            if (in_array('name', $attributeCodes, true)) {
                foreach ($entityIds as $entityId) {
                    if (!isset($attributesPerEntity[$entityId])) {
                        $attributesPerEntity[$entityId] = [
                                'id' => $entityId,
                                'entity_id' => $entityId,
                                'store_id' => Store::DEFAULT_STORE_ID,
                            ];
                    }
                }
            }
            return \array_map(function ($data) use ($attributeCodes) {
                return $this->formatEavAttributesArray($data, $attributeCodes);
            }, $attributesPerEntity);
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            throw new UnableRetrieveData('Unable to retrieve category eav attributes');
        }
    }

    /**
     * Format eav attributes array
     *
     * @param array $array
     * @param array $attributeCodes
     *
     * @return array
     */
    private function formatEavAttributesArray(array $array, array $attributeCodes) : array
    {
        $includeAttributes = [];

        foreach ($attributeCodes as $attribute) {
            $includeAttributes[$this->nameConverter->toCamelCase($attribute)] = $array[$attribute] ?? null;
        }

        return $includeAttributes;
    }
}
