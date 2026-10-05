<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product;

use Magento\CatalogDataExporter\Model\Provider\EavAttributes\EavAttributeOptionValueResolver;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Class for Attribute Metadata
 *
 * @see EavAttributeOptionValueResolver for the actual implementation. Kept for backward
 *      compatibility: resolves option labels for `catalog_product_entity`.
 */
class AttributeMetadata extends EavAttributeOptionValueResolver
{
    /**
     * @var PriceCurrencyInterface
     */
    private PriceCurrencyInterface $priceCurrency;

    /**
     * @param ResourceConnection $resourceConnection
     * @param PriceCurrencyInterface|null $priceCurrency
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ?PriceCurrencyInterface $priceCurrency = null
    ) {
        parent::__construct($resourceConnection, 'catalog_product_entity');
        $this->priceCurrency = $priceCurrency
            ?? ObjectManager::getInstance()->get(PriceCurrencyInterface::class);
    }

    /**
     * Returns attribute real value, converting price-type attributes to store currency
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
        if (($attributeMetadata['frontend_input'] ?? null) === 'price') {
            // Value is intentionally formatted in the default currency, as it's done for prices.
            $value = number_format($this->priceCurrency->convertAndRound((float)$value), 2, '.', '');
        }
        return parent::getAttributeValue($attributeCode, $storeViewCode, $value);
    }
}
