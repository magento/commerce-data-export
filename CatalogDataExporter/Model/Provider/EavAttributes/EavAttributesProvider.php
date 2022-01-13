<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\EavAttributes;

use Magento\CatalogDataExporter\Model\Query\Eav\EavAttributeQueryBuilderInterface;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\DataExporter\Sql\FieldToPropertyNameConverter;
use Magento\Framework\App\ResourceConnection;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

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

            return \array_map(function ($data) use ($attributeCodes) {
                return $this->formatEavAttributesArray($data, $attributeCodes);
            }, $this->attributesDataConverter->convert($attributes));
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
