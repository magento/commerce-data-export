<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\CatalogProductNumericAttribute\Plugin\CatalogGraphQl\Model\Config;

use Magento\CatalogGraphQl\Model\Config\FilterAttributeReader;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;

/**
 * Remaps "numeric" attributes from the default FilterMatchTypeInput to FilterRangeTypeInput
 * in the GraphQL product filter config so they support range queries.
 *
 * The core getFilterType() is private and has no mapping for "numeric", so it falls back
 * to FilterMatchTypeInput. This after-plugin on read() corrects the type.
 */
class FilterAttributeReaderPlugin
{
    private const FRONTEND_INPUT = 'numeric';
    private const FILTER_RANGE_TYPE = 'FilterRangeTypeInput';

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * After read(), remap numeric attributes to FilterRangeTypeInput.
     *
     * @param FilterAttributeReader $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterRead(FilterAttributeReader $subject, array $result): array
    {
        $numericCodes = $this->getNumericAttributeCodes();
        if (empty($numericCodes)) {
            return $result;
        }

        foreach ($result as $type => $typeConfig) {
            if (!isset($typeConfig['fields'])) {
                continue;
            }

            foreach ($numericCodes as $code) {
                if (isset($typeConfig['fields'][$code])) {
                    $result[$type]['fields'][$code]['type'] = self::FILTER_RANGE_TYPE;
                }
            }
        }

        return $result;
    }

    /**
     * Get attribute codes that have frontend_input = "numeric".
     *
     * @return string[]
     */
    private function getNumericAttributeCodes(): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('frontend_input', self::FRONTEND_INPUT);

        $codes = [];
        foreach ($collection->getItems() as $attribute) {
            $codes[] = $attribute->getAttributeCode();
        }
        return $codes;
    }
}
