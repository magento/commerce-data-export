<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurableProductDataExporter\Model\Provider\Product;

use Exception;
use Magento\CatalogDataExporter\Model\Provider\Product\OptionProviderInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProductDataExporter\Model\Query\ProductOptionQuery;
use Magento\ConfigurableProductDataExporter\Model\Query\ProductOptionValueQuery;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\ColumnValueExpression;
use Magento\Swatches\Helper\Media as MediaHelper;
use Magento\Swatches\Model\Swatch;

/**
 * Configurable product options data provider
 */
class Options implements OptionProviderInterface
{

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
     * @var ConfigurableOptionValueUid
     */
    private $optionValueUid;

    /**
     * @var MediaHelper
     */
    private $mediaHelper;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ProductOptionQuery $productOptionQuery
     * @param ProductOptionValueQuery $productOptionValueQuery
     * @param ConfigurableOptionValueUid $optionValueUid
     * @param MediaHelper $mediaHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ProductOptionQuery $productOptionQuery,
        ProductOptionValueQuery $productOptionValueQuery,
        ConfigurableOptionValueUid $optionValueUid,
        MediaHelper $mediaHelper,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->productOptionQuery = $productOptionQuery;
        $this->productOptionValueQuery = $productOptionValueQuery;
        $this->optionValueUid = $optionValueUid;
        $this->mediaHelper = $mediaHelper;
        $this->logger = $logger;
    }

    /**
     * Returns table name
     *
     * @param string $reference
     * @return string
     */
    private function getTable(string $reference)
    {
        return $this->resourceConnection->getTableName($reference);
    }

    /**
     * Returns possible attribute valies for a product
     *
     * @param int $entityId
     * @param int $attributeId
     * @return array
     */
    private function getPossibleAttributeValues($entityId, $attributeId)
    {
        $connection = $this->resourceConnection->getConnection();
        $joinField = $connection->getAutoIncrementField($this->getTable('catalog_product_entity'));
        $select = $connection->select()
            ->from(['cpe' => $this->getTable('catalog_product_entity')], [])
            ->join(
                ['psl' => $this->getTable('catalog_product_super_link')],
                sprintf('psl.parent_id = cpe.%s', $joinField),
                []
            )
            ->join(
                ['iss' => $this->getTable('cataloginventory_stock_status')],
                'iss.product_id = psl.product_id AND iss.stock_status = 1',
                []
            )
            ->join(
                ['cpc' => $this->getTable('catalog_product_entity')],
                'cpc.entity_id = psl.product_id',
                []
            )
            ->join(
                ['cpi' => $this->getTable('catalog_product_entity_int')],
                sprintf(
                    'cpi.%1$s = cpc.%1$s AND cpi.store_id = 0 AND cpi.attribute_id = %2$d',
                    $joinField,
                    $attributeId
                ),
                []
            )
            ->where('cpe.entity_id = ?', $entityId)
            ->columns(
                new ColumnValueExpression('DISTINCT cpi.value')
            );
        return $connection->fetchCol($select);
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
        $arguments['attributes'] = $this->getAttributeIds($arguments);
        $select = $this->productOptionValueQuery->getQuery($arguments);

        $cursor = $this->resourceConnection->getConnection()->query($select);

        $data = [];
        while ($row = $cursor->fetch()) {
            $data[$row['attribute_id']][$row['storeViewCode']][$row['optionId']] = [
                'id' => $this->optionValueUid->resolve($row['attribute_id'], $row['optionId']),
                'label' => $row['label'],
                'colorHex' => $row['swatchType'] == Swatch::SWATCH_TYPE_VISUAL_COLOR
                    ? $row['swatchValue'] : null,
                'imageUrl' => $row['swatchType'] == Swatch::SWATCH_TYPE_VISUAL_IMAGE
                    ? $this->mediaHelper->getSwatchMediaUrl() . $row['swatchValue'] : null,
                'customSwatchValue' => !in_array(
                    $row['swatchType'],
                    [Swatch::SWATCH_TYPE_TEXTUAL, Swatch::SWATCH_TYPE_VISUAL_COLOR, Swatch::SWATCH_TYPE_VISUAL_IMAGE]
                ) ? $row['swatchValue'] : null
            ];
        }
        return $data;
    }

    /**
     * Returns attribute IDs associated with this product
     *
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
                'sortOrder' => $row['position'],
                'swatchType' => $row['swatchType']
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
        $temp = [];

        $queryArguments = [];
        foreach ($values as $value) {
            if (!isset($value['productId'], $value['type'], $value['storeViewCode'])
                || $value['type'] !== Configurable::TYPE_CODE) {
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

            $cursor = $this->resourceConnection->getConnection()->query($select);

            while ($row = $cursor->fetch()) {
                if (!isset($temp[$row['productId'] . '-' . $row['attribute_id']])) {
                    $temp[$row['productId'] . '-' . $row['attribute_id']] =
                        $this->getPossibleAttributeValues($row['productId'], $row['attribute_id']);
                }
                $filter = $temp[$row['productId'] . '-' . $row['attribute_id']];

                $key = $this->getOptionKey($row);
                $options[$key] = $options[$key] ?? $this->formatOptionsRow($row);

                if (isset($optionValuesData[$row['attribute_id']][$row['storeViewCode']])) {
                    $options[$key]['optionsV2']['values'] = $this->getAssignedAttributeValues(
                        $optionValuesData[$row['attribute_id']][$row['storeViewCode']],
                        $filter
                    );
                }
            }
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            throw new UnableRetrieveData('Unable to retrieve configurable product options data');
        }
        return $options;
    }

    /**
     * Filter values
     *
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
