<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Fixture;

use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;

/**
 * Creates a parent product with two related links ordered so that without an explicit
 * sort the DB returns them in insertion order (zzz before aaa), exposing the ordering bug.
 */
class ProductsWithRelatedLinks implements RevertibleDataFixtureInterface
{
    public const PARENT_SKU = 'link-parent-product';
    public const LINKED_SKU_FIRST  = 'link-product-zzz';
    public const LINKED_SKU_SECOND = 'link-product-aaa';

    public function __construct(
        private readonly ProductFactory $productFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductLinkInterfaceFactory $productLinkFactory,
        private readonly Registry $registry
    ) {
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function apply(array $data = []): ?DataObject
    {
        // Save link-product-zzz FIRST so it gets a lower entity_id / link_id.
        $this->createSimpleProduct(self::LINKED_SKU_FIRST, 'Link Product ZZZ');

        // Save link-product-aaa SECOND so it gets a higher entity_id / link_id.
        $this->createSimpleProduct(self::LINKED_SKU_SECOND, 'Link Product AAA');

        // Save parent last and add zzz as the first related link so it receives
        // a lower link_id in catalog_product_link - without ORDER BY MySQL returns it first.
        $linkZzz = $this->productLinkFactory->create();
        $linkZzz->setSku(self::PARENT_SKU)
            ->setLinkedProductSku(self::LINKED_SKU_FIRST)
            ->setPosition(1)
            ->setLinkType('related');

        $linkAaa = $this->productLinkFactory->create();
        $linkAaa->setSku(self::PARENT_SKU)
            ->setLinkedProductSku(self::LINKED_SKU_SECOND)
            ->setPosition(2)
            ->setLinkType('related');

        $parent = $this->productFactory->create();
        $parent->isObjectNew(true);
        $parent->setTypeId(Type::TYPE_SIMPLE)
            ->setAttributeSetId(4)
            ->setName('Link Parent Product')
            ->setSku(self::PARENT_SKU)
            ->setPrice(10)
            ->setVisibility(Visibility::VISIBILITY_BOTH)
            ->setStatus(Status::STATUS_ENABLED)
            ->setWebsiteIds([1])
            ->setStockData(['use_config_manage_stock' => 1, 'qty' => 10, 'is_in_stock' => 1])
            ->setProductLinks([$linkZzz, $linkAaa]);
        $this->productRepository->save($parent);

        return null;
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function revert(DataObject $data): void
    {
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', true);

        foreach ([self::PARENT_SKU, self::LINKED_SKU_FIRST, self::LINKED_SKU_SECOND] as $sku) {
            try {
                $this->productRepository->delete($this->productRepository->get($sku));
            } catch (\Throwable) {
            }
        }

        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', false);
    }

    private function createSimpleProduct(string $sku, string $name): void
    {
        $product = $this->productFactory->create();
        $product->isObjectNew(true);
        $product->setTypeId(Type::TYPE_SIMPLE)
            ->setAttributeSetId(4)
            ->setName($name)
            ->setSku($sku)
            ->setPrice(10)
            ->setVisibility(Visibility::VISIBILITY_BOTH)
            ->setStatus(Status::STATUS_ENABLED)
            ->setWebsiteIds([1])
            ->setStockData(['use_config_manage_stock' => 1, 'qty' => 10, 'is_in_stock' => 1]);
        $this->productRepository->save($product);
    }
}
