<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Fixture;

use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Store\Api\Data\StoreInterfaceFactory;
use Magento\Store\Model\ResourceModel\Store as StoreResource;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;

/**
 * Creates a simple product with store-only EAV values covering all 5 backend types
 * (varchar, text, int, decimal, datetime), with no admin-store (store_id=0) values.
 */
class ProductEavAllBackendTypes implements RevertibleDataFixtureInterface
{
    private const STORE_CODE = 'fixture_second_store';
    private const PRODUCT_SKU = 'product_eav_types';
    private const PRODUCT_ID = 60;
    private const CATEGORY_ID = 100;

    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly StoreInterfaceFactory $storeFactory,
        private readonly StoreResource $storeResource,
        private readonly StoreManagerInterface $storeManager,
        private readonly ProductFactory $productFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CategoryFactory $categoryFactory,
        private readonly CategoryLinkManagementInterface $categoryLinkManagement,
        private readonly Registry $registry
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply(array $data = []): ?DataObject
    {
        $secondStoreId = $this->ensureStoreExists();
        $this->ensureCategoryExists();

        $product = $this->productFactory->create();
        $product->isObjectNew(true);
        $product->setTypeId(Type::TYPE_SIMPLE)
            ->setId(self::PRODUCT_ID)
            ->setAttributeSetId(4)
            ->setName('Product EAV Types Test')
            ->setSku(self::PRODUCT_SKU)
            ->setPrice(10)
            ->setWeight(5.0)
            ->setMetaTitle('Admin Meta Title')
            ->setShortDescription('Admin Short Desc')
            ->setCustomAttribute('news_from_date', '2024-01-01 00:00:00')
            ->setVisibility(Visibility::VISIBILITY_BOTH)
            ->setStatus(Status::STATUS_ENABLED)
            ->setWebsiteIds([1])
            ->setStockData(['use_config_manage_stock' => 1, 'qty' => 10, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
            ->save();

        $this->categoryLinkManagement->assignProductToCategories(self::PRODUCT_SKU, [self::CATEGORY_ID]);

        $connection = $this->resourceConnection->getConnection();
        $productEntityTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $linkField = $connection->getAutoIncrementField($productEntityTable);
        $linkFieldValue = (int)$connection->fetchOne(
            $connection->select()
                ->from(['e' => $productEntityTable], [$linkField])
                ->where('e.entity_id = ?', self::PRODUCT_ID)
        );

        $this->setupStoreOnlyValue(
            'catalog_product_entity_varchar',
            'meta_title',
            'Store Meta Title',
            $linkField,
            $linkFieldValue,
            $secondStoreId
        );
        $this->setupStoreOnlyValue(
            'catalog_product_entity_text',
            'short_description',
            'Store Short Desc',
            $linkField,
            $linkFieldValue,
            $secondStoreId
        );
        $this->setupStoreOnlyValue(
            'catalog_product_entity_int',
            'status',
            Status::STATUS_ENABLED,
            $linkField,
            $linkFieldValue,
            $secondStoreId
        );
        $this->setupStoreOnlyValue(
            'catalog_product_entity_decimal',
            'weight',
            2.5,
            $linkField,
            $linkFieldValue,
            $secondStoreId
        );
        $this->setupStoreOnlyValue(
            'catalog_product_entity_datetime',
            'news_from_date',
            '2024-06-15 00:00:00',
            $linkField,
            $linkFieldValue,
            $secondStoreId
        );

        return new DataObject(['sku' => self::PRODUCT_SKU, 'id' => self::PRODUCT_ID]);
    }

    /**
     * @inheritdoc
     */
    public function revert(DataObject $data): void
    {
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', true);

        try {
            $product = $this->productRepository->get(self::PRODUCT_SKU);
            $this->productRepository->delete($product);
        } catch (\Exception) {
            // nothing to delete
        }

        $store = $this->storeFactory->create();
        $this->storeResource->load($store, self::STORE_CODE, 'code');
        if ($store->getId()) {
            $this->storeResource->delete($store);
            $this->storeManager->reinitStores();
        }

        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', false);
    }

    /**
     * Creates the second store if it does not already exist.
     *
     * @return int store_id
     */
    private function ensureStoreExists(): int
    {
        $store = $this->storeFactory->create();
        $this->storeResource->load($store, self::STORE_CODE, 'code');
        if (!$store->getId()) {
            $store->setCode(self::STORE_CODE)
                ->setWebsiteId((int)$this->storeManager->getWebsite()->getId())
                ->setGroupId((int)$this->storeManager->getWebsite()->getDefaultGroupId())
                ->setName('Fixture Store')
                ->setSortOrder(10)
                ->setIsActive(1);
            $this->storeResource->save($store);
            $this->storeManager->reinitStores();
        }
        return (int)$store->getId();
    }

    /**
     * Creates category used for product assignment if it does not already exist.
     */
    private function ensureCategoryExists(): void
    {
        $category = $this->categoryFactory->create();
        $category->load(self::CATEGORY_ID);
        if (!$category->getId()) {
            $category->isObjectNew(true);
            $category->setId(self::CATEGORY_ID)
                ->setName('SaaS Category')
                ->setParentId(2)
                ->setPath('1/2/' . self::CATEGORY_ID)
                ->setUrlKey('saas-category')
                ->setLevel(2)
                ->setAvailableSortBy(['name', 'price'])
                ->setDefaultSortBy('name')
                ->setIsActive(true)
                ->setPosition(1)
                ->setStoreId(1)
                ->save();
        }
    }

    /**
     * Removes the admin-store (store_id=0) EAV row for the given attribute and inserts a
     * store-specific row, simulating the scenario where only a store-view value exists.
     *
     * @param string $backendTable
     * @param string $attributeCode
     * @param mixed $storeValue
     * @param string $linkField
     * @param int $linkFieldValue
     * @param int $storeId
     */
    private function setupStoreOnlyValue(
        string $backendTable,
        string $attributeCode,
        mixed $storeValue,
        string $linkField,
        int $linkFieldValue,
        int $storeId
    ): void {
        $connection = $this->resourceConnection->getConnection();
        $attributeId = $this->getAttributeId($attributeCode);
        if ($attributeId === 0) {
            return;
        }
        $table = $this->resourceConnection->getTableName($backendTable);
        $connection->delete($table, [
            $linkField . ' = ?' => $linkFieldValue,
            'attribute_id = ?' => $attributeId,
            'store_id = ?' => 0,
        ]);
        $connection->insertOnDuplicate($table, [
            $linkField => $linkFieldValue,
            'attribute_id' => $attributeId,
            'store_id' => $storeId,
            'value' => $storeValue,
        ], ['value']);
    }

    /**
     * Returns attribute_id for a product EAV attribute by code.
     *
     * @param string $attributeCode
     * @return int
     */
    private function getAttributeId(string $attributeCode): int
    {
        $connection = $this->resourceConnection->getConnection();
        return (int)$connection->fetchOne(
            $connection->select()
                ->from(['a' => $this->resourceConnection->getTableName('eav_attribute')], ['attribute_id'])
                ->join(
                    ['et' => $this->resourceConnection->getTableName('eav_entity_type')],
                    'a.entity_type_id = et.entity_type_id',
                    []
                )
                ->where('et.entity_table = ?', 'catalog_product_entity')
                ->where('a.attribute_code = ?', $attributeCode)
        );
    }
}
