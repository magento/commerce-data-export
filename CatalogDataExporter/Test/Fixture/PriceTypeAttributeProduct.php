<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Fixture;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Entity\Attribute as CatalogEntityAttribute;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Eav\Model\Entity\Attribute as EavAttribute;
use Magento\Eav\Model\Entity\TypeFactory as EntityTypeFactory;
use Magento\Framework\DataObject;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;

/**
 * Creates a user-defined `price` frontend-input attribute (decimal backend) and a simple product with a
 * non-normalized value (e.g. 12.5), which is stored by Magento as "12.500000".
 *
 * Used by ProductPriceTypeAttributeTest to verify price-type attribute values are formatted as prices.
 */
class PriceTypeAttributeProduct implements RevertibleDataFixtureInterface
{
    public const ATTRIBUTE_CODE = 'custom_price_attr';
    public const SKU = 'price-type-attribute-product';

    public function __construct(
        private readonly ObjectManagerInterface $objectManager,
        private readonly EntityTypeFactory $entityTypeFactory,
        private readonly ProductFactory $productFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly Registry $registry
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply(array $data = []): ?DataObject
    {
        $entityType = $this->entityTypeFactory->create()->loadByCode('catalog_product');
        $attributeSetId = (int)$entityType->getDefaultAttributeSetId();
        $set = $this->objectManager->create(\Magento\Eav\Model\Entity\Attribute\Set::class);
        $set->load($attributeSetId);

        $attribute = $this->objectManager->create(CatalogEntityAttribute::class);
        $attribute->load(self::ATTRIBUTE_CODE, 'attribute_code');
        if (!$attribute->getId()) {
            $attribute->setData([
                'attribute_code' => self::ATTRIBUTE_CODE,
                'entity_type_id' => (int)$entityType->getId(),
                'attribute_set_id' => $attributeSetId,
                'attribute_group_id' => (int)$set->getDefaultGroupId(),
                'is_global' => 1,
                'is_user_defined' => 1,
                'frontend_input' => 'price',
                'frontend_label' => ['Custom Price'],
                'backend_type' => 'decimal',
                'is_required' => 0,
                'is_unique' => 0,
                'used_in_product_listing' => 1,
                'is_visible_on_front' => 1,
            ]);
            $attribute->save();
        }

        $product = $this->productFactory->create();
        $product->isObjectNew(true);
        $product->setTypeId(Type::TYPE_SIMPLE)
            ->setAttributeSetId($attributeSetId)
            ->setName('Price Type Attribute Product')
            ->setSku(self::SKU)
            ->setTaxClassId(2)
            ->setPrice(50)
            ->setWeight(1)
            ->setVisibility(Visibility::VISIBILITY_BOTH)
            ->setStatus(Status::STATUS_ENABLED)
            ->setWebsiteIds([1])
            ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
            ->setData(self::ATTRIBUTE_CODE, $data['price_attribute_value'])
            ->save();

        return new DataObject(['sku' => self::SKU]);
    }

    /**
     * @inheritdoc
     */
    public function revert(DataObject $data): void
    {
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', true);

        try {
            $this->productRepository->delete($this->productRepository->get(self::SKU));
        } catch (\Throwable) {
            // nothing to delete
        }
        try {
            $attribute = $this->objectManager->create(EavAttribute::class);
            $attribute->load(self::ATTRIBUTE_CODE, 'attribute_code');
            if ($attribute->getId()) {
                $attribute->delete();
            }
        } catch (\Throwable) {
            // nothing to delete
        }

        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', false);
    }
}
