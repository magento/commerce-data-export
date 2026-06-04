<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Fixture;

use Magento\Bundle\Api\Data\LinkInterfaceFactory;
use Magento\Bundle\Api\Data\OptionInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as CatalogAttribute;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory as ConfigurableOptionsFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Catalog\Setup\CategorySetup;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\TypeFactory as EntityTypeFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory as OptionCollectionFactory;
use Magento\Framework\DataObject;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;

/**
 * Creates a configurable product (with one select attribute, two simple variants)
 * and a bundle product (with one required option linking a simple product).
 */
class ConfigurableAndBundleProducts implements RevertibleDataFixtureInterface
{
    public const CONFIGURABLE_SKU = 'options-v2-test-configurable';
    public const BUNDLE_SKU = 'options-v2-test-bundle';
    public const ATTRIBUTE_CODE = 'options_v2_test_attr';

    private const SIMPLE_VARIANT_SKU_1 = 'options-v2-test-simple-1';
    private const SIMPLE_VARIANT_SKU_2 = 'options-v2-test-simple-2';
    private const BUNDLE_SIMPLE_SKU = 'options-v2-test-bundle-simple';

    public function __construct(
        private readonly ObjectManagerInterface $objectManager,
        private readonly ProductFactory $productFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ConfigurableOptionsFactory $configurableOptionsFactory,
        private readonly OptionInterfaceFactory $bundleOptionFactory,
        private readonly LinkInterfaceFactory $bundleLinkFactory,
        private readonly EntityTypeFactory $entityTypeFactory,
        private readonly OptionCollectionFactory $optionCollectionFactory,
        private readonly EavConfig $eavConfig,
        private readonly AttributeRepositoryInterface $attributeRepository,
        private readonly CategorySetup $categorySetup,
        private readonly Registry $registry
    ) {
    }

    public function apply(array $data = []): ?DataObject
    {
        $entityType = $this->entityTypeFactory->create()->loadByCode('catalog_product');
        $attributeSetId = (int)$entityType->getDefaultAttributeSetId();

        [$optionId1, $optionId2] = $this->ensureAttribute();
        $this->createSimpleVariant(self::SIMPLE_VARIANT_SKU_1, 'Variant One', $attributeSetId, $optionId1);
        $this->createSimpleVariant(self::SIMPLE_VARIANT_SKU_2, 'Variant Two', $attributeSetId, $optionId2);
        $this->createConfigurableProduct($attributeSetId);

        $this->createSimpleProduct(self::BUNDLE_SIMPLE_SKU, 'Bundle Simple', $attributeSetId);
        $this->createBundleProduct($attributeSetId);

        return null;
    }

    public function revert(DataObject $data): void
    {
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', true);

        $skus = [
            self::CONFIGURABLE_SKU,
            self::BUNDLE_SKU,
            self::SIMPLE_VARIANT_SKU_1,
            self::SIMPLE_VARIANT_SKU_2,
            self::BUNDLE_SIMPLE_SKU
        ];
        foreach ($skus as $sku) {
            try {
                $this->productRepository->delete($this->productRepository->get($sku));
            } catch (\Throwable) {
            }
        }

        try {
            $attribute = $this->loadAttribute();
            if ($attribute->getId()) {
                $attribute->delete();
            }
        } catch (\Throwable) {
        }

        $this->eavConfig->clear();
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', false);
    }

    private function ensureAttribute(): array
    {
        $this->eavConfig->clear();
        $existing = $this->eavConfig->getAttribute('catalog_product', self::ATTRIBUTE_CODE);
        if (!$existing || !$existing->getId()) {
            /** @var CatalogAttribute $attribute */
            $attribute = $this->objectManager->create(CatalogAttribute::class);
            $attribute->setData([
                'attribute_code' => self::ATTRIBUTE_CODE,
                'entity_type_id' => $this->categorySetup->getEntityTypeId('catalog_product'),
                'is_global' => 1,
                'is_user_defined' => 1,
                'frontend_input' => 'select',
                'backend_type' => 'int',
                'is_unique' => 0,
                'is_required' => 0,
                'is_searchable' => 0,
                'is_visible' => 1,
                'is_comparable' => 0,
                'is_filterable' => 0,
                'is_filterable_in_search' => 0,
                'is_visible_on_front' => 0,
                'used_in_product_listing' => 0,
                'frontend_label' => ['Test Option Attr'],
                'option' => [
                    'value' => ['opt_a' => ['Option A'], 'opt_b' => ['Option B']],
                    'order' => ['opt_a' => 1, 'opt_b' => 2],
                ],
            ]);
            $this->attributeRepository->save($attribute);
        }

        $this->categorySetup->addAttributeToGroup('catalog_product', 'Default', 'General', self::ATTRIBUTE_CODE);
        $this->eavConfig->clear();

        return $this->resolveOptionIds();
    }

    private function resolveOptionIds(): array
    {
        $attribute = $this->loadAttribute();
        $collection = $this->optionCollectionFactory->create();
        $collection->setAttributeFilter((int)$attribute->getId())->setStoreFilter(0);

        $ids = [];
        foreach ($collection as $option) {
            $ids[] = (int)$option->getOptionId();
        }
        if (count($ids) < 2) {
            throw new \RuntimeException('Could not resolve option ids for ' . self::ATTRIBUTE_CODE);
        }
        return [$ids[0], $ids[1]];
    }

    private function createSimpleVariant(string $sku, string $name, int $attributeSetId, int $optionId): void
    {
        $product = $this->productFactory->create();
        $product->isObjectNew(true);
        $product->setTypeId(Type::TYPE_SIMPLE)
            ->setAttributeSetId($attributeSetId)
            ->setName($name)
            ->setSku($sku)
            ->setPrice(10)
            ->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE)
            ->setStatus(Status::STATUS_ENABLED)
            ->setWebsiteIds([1])
            ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_in_stock' => 1])
            ->setCustomAttribute(self::ATTRIBUTE_CODE, $optionId);
        $this->productRepository->save($product);
    }

    private function createSimpleProduct(string $sku, string $name, int $attributeSetId): void
    {
        $product = $this->productFactory->create();
        $product->isObjectNew(true);
        $product->setTypeId(Type::TYPE_SIMPLE)
            ->setAttributeSetId($attributeSetId)
            ->setName($name)
            ->setSku($sku)
            ->setPrice(10)
            ->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE)
            ->setStatus(Status::STATUS_ENABLED)
            ->setWebsiteIds([1])
            ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_in_stock' => 1]);
        $this->productRepository->save($product);
    }

    private function createConfigurableProduct(int $attributeSetId): void
    {
        $attribute = $this->attributeRepository->get('catalog_product', self::ATTRIBUTE_CODE);
        $variant1 = $this->productRepository->get(self::SIMPLE_VARIANT_SKU_1);
        $variant2 = $this->productRepository->get(self::SIMPLE_VARIANT_SKU_2);

        $options = $this->configurableOptionsFactory->create([
            [
                'attribute_id' => $attribute->getId(),
                'code' => self::ATTRIBUTE_CODE,
                'label' => $attribute->getStoreLabel(),
                'position' => '0',
                'values' => [
                    [
                        'label' => 'Option A',
                        'attribute_id' => $attribute->getId(),
                        'value_index' => $variant1->getData(self::ATTRIBUTE_CODE)
                    ],
                    [
                        'label' => 'Option B',
                        'attribute_id' => $attribute->getId(),
                        'value_index' => $variant2->getData(self::ATTRIBUTE_CODE)
                    ],
                ],
            ],
        ]);

        $product = $this->productFactory->create();
        $product->isObjectNew(true);
        $extension = $product->getExtensionAttributes();
        $extension->setConfigurableProductOptions($options);
        $extension->setConfigurableProductLinks([$variant1->getId(), $variant2->getId()]);
        $product->setExtensionAttributes($extension);
        $product->setTypeId(Configurable::TYPE_CODE)
            ->setAttributeSetId($attributeSetId)
            ->setName('Options V2 Test Configurable')
            ->setSku(self::CONFIGURABLE_SKU)
            ->setPrice(20)
            ->setVisibility(Visibility::VISIBILITY_BOTH)
            ->setStatus(Status::STATUS_ENABLED)
            ->setWebsiteIds([1])
            ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_in_stock' => 1]);
        $this->productRepository->save($product);
    }

    private function createBundleProduct(int $attributeSetId): void
    {
        $bundleSimple = $this->productRepository->get(self::BUNDLE_SIMPLE_SKU);

        $link = $this->bundleLinkFactory->create();
        $link->setSku($bundleSimple->getSku())
            ->setQty(1)
            ->setPrice(5.00)
            ->setCanChangeQuantity(1);

        $option = $this->bundleOptionFactory->create();
        $option->setTitle('Bundle Items')
            ->setType('select')
            ->setRequired(1)
            ->setPosition(0)
            ->setProductLinks([$link]);

        $product = $this->productFactory->create();
        $product->isObjectNew(true);
        $extension = $product->getExtensionAttributes();
        $extension->setBundleProductOptions([$option]);
        $product->setExtensionAttributes($extension);
        $product->setTypeId(\Magento\Bundle\Model\Product\Type::TYPE_CODE)
            ->setAttributeSetId($attributeSetId)
            ->setName('Options V2 Test Bundle')
            ->setSku(self::BUNDLE_SKU)
            ->setPriceType(1)
            ->setPriceView(0)
            ->setSkuType(1)
            ->setWeightType(1)
            ->setPrice(20)
            ->setVisibility(Visibility::VISIBILITY_BOTH)
            ->setStatus(Status::STATUS_ENABLED)
            ->setWebsiteIds([1])
            ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_in_stock' => 1]);
        $this->productRepository->save($product, true);
    }

    private function loadAttribute(): CatalogAttribute
    {
        /** @var CatalogAttribute $attribute */
        $attribute = $this->objectManager->create(CatalogAttribute::class);
        $attribute->load(self::ATTRIBUTE_CODE, 'attribute_code');
        return $attribute;
    }
}
