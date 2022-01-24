<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Class for Attribute Metadata
 */
class AttributeMetadata
{
    /**
     * @var
     */
    private $attributeMetadata;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * AttributeMetadata constructor.
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get select for attributes
     *
     * @param string $attributeCode
     * @return Select
     */
    private function getAttributesSelect(string $attributeCode): Select
    {
        $connection = $this->resourceConnection->getConnection();
        return $connection->select()
            ->from(['a' => $this->resourceConnection->getTableName('eav_attribute')])
            ->join(
                ['t' => $this->resourceConnection->getTableName('eav_entity_type')],
                't.entity_type_id = a.entity_type_id',
                []
            )
            ->where('t.entity_table = ?', 'catalog_product_entity')
            ->where('a.attribute_code = ?', $attributeCode);
    }

    /**
     * Get options raw
     *
     * @param string $attributeCode
     * @return Select
     */
    private function getRawOptionsSelect(string $attributeCode) : Select
    {
        return $this->getAttributesSelect($attributeCode)
            ->joinLeft(
                ['o' => $this->resourceConnection->getTableName('eav_attribute_option')],
                'o.attribute_id = a.attribute_id',
                [
                    'optionId' => 'o.option_id'
                ]
            )
            ->joinLeft(
                ['v' => $this->resourceConnection->getTableName('eav_attribute_option_value')],
                'o.option_id  = v.option_id',
                ['optionValue' => 'v.value']
            )
            ->joinLeft(
                ['s' => $this->resourceConnection->getTableName('store')],
                'v.store_id = s.store_id',
                ['storeViewCode' => 's.code']
            );
    }

    /**
     * Load attributes metadata
     *
     * @param string $attributeCode
     * @throws \Zend_Db_Statement_Exception
     */
    private function loadAttributesMetadata(string $attributeCode): void
    {
        $connection = $this->resourceConnection->getConnection();
        $cursor = $connection->query($this->getAttributesSelect($attributeCode));
        while ($row = $cursor->fetch()) {
            $this->attributeMetadata[$row['attribute_code']] = $row;
        }
        $cursor = $connection->query($this->getRawOptionsSelect($attributeCode));
        while ($row = $cursor->fetch()) {
            if ($row['source_model'] == \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class) {
                $this->attributeMetadata[$row['attribute_code']]['options']['admin'][0] = 'no';
                $this->attributeMetadata[$row['attribute_code']]['options']['admin'][1] = 'yes';
            } elseif (isset($row['optionId'])) {
                $this->attributeMetadata[$row['attribute_code']]['options'][$row['storeViewCode']][$row['optionId']] =
                    $row['optionValue'];
            }
        }
    }

    /**
     * Get metadata for an attribute code
     *
     * @param string $attributeCode
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function getAttributeMetadata(string $attributeCode): array
    {
        return $this->getAttributesMetadata($attributeCode);
    }

    /**
     * Returns attribute real value
     *
     * @param string $attributeCode
     * @param string $storeViewCode
     * @param string $value
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function getAttributeValue(string $attributeCode, string $storeViewCode, string $value): array
    {
        $attributeMetadata = $this->getAttributeMetadata($attributeCode);
        $output = null;
        if (!isset($attributeMetadata['options'])) {
            return [$value];
        }
        $optionIds = explode(',', $value);
        foreach ($optionIds as $optionId) {
            if (isset($attributeMetadata['options'][$storeViewCode][$optionId])) {
                $output[] = $attributeMetadata['options'][$storeViewCode][$optionId];
            } elseif (isset($attributeMetadata['options']['admin'][$optionId])) {
                $output[] = $attributeMetadata['options']['admin'][$optionId];
            } else {
                $output[] = $optionId;
            }
        }

        return $output;
    }

    /**
     * Returns attribute real value id
     *
     * @param string $attributeCode
     * @param string $storeViewCode
     * @param string $value
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function getAttributeValueId(string $attributeCode, string $storeViewCode, string $value): array
    {
        $attributeMetadata = $this->getAttributeMetadata($attributeCode);
        $output = [];
        $optionIds = explode(',', $value);
        foreach ($optionIds as $optionId) {
            if (isset($attributeMetadata['options'][$storeViewCode][$optionId]) ||
                isset($attributeMetadata['options']['admin'][$optionId])) {
                $output[] = $optionId;
            }
        }

        return $output;
    }

    /**
     * Get options
     *
     * @param string $attributeCode
     * @param string $storeViewCode
     * @param string $value
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function getOptionById(string $attributeCode, string $storeViewCode, string $value): array
    {
        $attributeMetadata = $this->getAttributeMetadata($attributeCode);
        $output = null;
        $optionIds = explode(',', $value);
        foreach ($optionIds as $optionId) {
            if (isset($attributeMetadata['options'][$storeViewCode][$optionId])) {
                $output[] = [
                    'id' => $optionId,
                    'value' => $attributeMetadata['options'][$storeViewCode][$optionId]
                ];
            } elseif (isset($attributeMetadata['options']['admin'][$optionId])) {
                $output[] = [
                    'id' => $optionId,
                    'value' => $attributeMetadata['options']['admin'][$optionId]
                ];
            } else {
                $output[] = [
                    'id' => -1,
                    'value' => $optionId
                ];
            }
        }
        return $output;
    }

    /**
     * Check for options for attribute code
     *
     * @param string $attributeCode
     * @return bool
     * @throws \Zend_Db_Statement_Exception
     */
    public function hasOptions(string $attributeCode): bool
    {
        $attribute = $this->getAttributeMetadata($attributeCode);
        return isset($attribute['options']);
    }

    /**
     * Get metadata for attributes
     *
     * @param string $attributeCode
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function getAttributesMetadata(string $attributeCode): array
    {
        if (empty($this->attributeMetadata[$attributeCode])) {
            $this->loadAttributesMetadata($attributeCode);
        }
        return $this->attributeMetadata[$attributeCode];
    }
}
