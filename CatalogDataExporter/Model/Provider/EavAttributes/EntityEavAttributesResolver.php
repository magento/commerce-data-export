<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\EavAttributes;

use Magento\CatalogDataExporter\Model\Resolver\AttributesResolver;
use Magento\DataExporter\Exception\UnableRetrieveData;

/**
 * Class responsible for preparing eav attributes data for products and categories
 */
class EntityEavAttributesResolver
{
    /**
     * @var EavAttributesProvider
     */
    private $eavAttributesProvider;

    /**
     * @var AttributesResolver
     */
    private $attributesResolver;

    /**
     * @var string[]
     */
    private $requiredAttributes;

    /**
     * @param EavAttributesProvider $eavAttributesProvider
     * @param AttributesResolver $attributesResolver
     * @param array $requiredAttributes
     */
    public function __construct(
        EavAttributesProvider $eavAttributesProvider,
        AttributesResolver $attributesResolver,
        array $requiredAttributes = []
    ) {
        $this->eavAttributesProvider = $eavAttributesProvider;
        $this->attributesResolver = $attributesResolver;
        $this->requiredAttributes = $requiredAttributes;
    }

    /**
     * Resolve entity eav attributes
     *
     * @param array $entitiesData // entity_id => attributes_array relation
     * @param string $storeCode
     *
     * @return array
     *
     * @throws UnableRetrieveData
     */
    public function resolve(array $entitiesData, string $storeCode)
    {
        $attributesArray = \array_filter($entitiesData);
        $entityIds = \array_keys(\array_diff_key($entitiesData, $attributesArray));
        $partialAttributesData = [];
        $fullAttributesData = [];

        if (!empty($attributesArray)) {
            $attributes = $this->attributesResolver->resolve(\array_unique(\array_merge(...$attributesArray)));

            // TODO implement specific provider call to eliminate requiredAttributes
            foreach ($this->requiredAttributes as $attribute) {
                $attributes[] = $attribute;
            }

            $partialAttributesData = $this->eavAttributesProvider->getEavAttributesData(
                \array_keys($attributesArray),
                $storeCode,
                $attributes
            );
        }

        if (!empty($entityIds)) {
            $fullAttributesData = $this->eavAttributesProvider->getEavAttributesData($entityIds, $storeCode);
        }

        $attributes = \array_replace($partialAttributesData, $fullAttributesData);
        if (empty($attributes)) {
            throw new UnableRetrieveData('Empty list of EAV attributes for products: ' . implode(',', $entityIds));
        }

        return $attributes;
    }
}
