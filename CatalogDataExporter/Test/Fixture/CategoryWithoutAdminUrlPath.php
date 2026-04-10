<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Fixture;

use Magento\Catalog\Api\CategoryRepositoryInterface;
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
 * Creates a category whose url_path exists only for a specific store view (store_id=0 row is absent),
 * and a simple product assigned to that category.
 *
 * This simulates the state AC leaves after moving a category: the admin-store url_path row is deleted
 * but store-view-specific url_path rows remain.
 */
class CategoryWithoutAdminUrlPath implements RevertibleDataFixtureInterface
{
    private const STORE_CODE = 'fixture_second_store';
    private const PRODUCT_SKU = 'product_cat_no_admin_url';
    private const PRODUCT_ID = 702;
    private const CATEGORY_ID = 701;
    private const CATEGORY_URL_KEY = 'cat-no-admin-url-701';

    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly StoreInterfaceFactory $storeFactory,
        private readonly StoreResource $storeResource,
        private readonly StoreManagerInterface $storeManager,
        private readonly ProductFactory $productFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CategoryFactory $categoryFactory,
        private readonly CategoryRepositoryInterface $categoryRepository,
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

        $category = $this->categoryFactory->create();
        $category->isObjectNew(true);
        $category->setId(self::CATEGORY_ID)
            ->setName('Category Without Admin URL Path')
            ->setParentId(2)
            ->setPath('1/2/' . self::CATEGORY_ID)
            ->setUrlKey(self::CATEGORY_URL_KEY)
            ->setLevel(2)
            ->setAvailableSortBy(['name'])
            ->setDefaultSortBy('name')
            ->setIsActive(true)
            ->setPosition(1)
            ->setStoreId(0)
            ->save();

        $product = $this->productFactory->create();
        $product->isObjectNew(true);
        $product->setTypeId(Type::TYPE_SIMPLE)
            ->setId(self::PRODUCT_ID)
            ->setAttributeSetId(4)
            ->setName('Product In Category Without Admin URL Path')
            ->setSku(self::PRODUCT_SKU)
            ->setPrice(10)
            ->setWeight(1)
            ->setVisibility(Visibility::VISIBILITY_BOTH)
            ->setStatus(Status::STATUS_ENABLED)
            ->setWebsiteIds([1])
            ->setStockData(['use_config_manage_stock' => 1, 'qty' => 10, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
            ->save();

        $this->categoryLinkManagement->assignProductToCategories(self::PRODUCT_SKU, [self::CATEGORY_ID]);

        $connection = $this->resourceConnection->getConnection();
        $categoryEntityTable = $this->resourceConnection->getTableName('catalog_category_entity');
        $linkField = $connection->getAutoIncrementField($categoryEntityTable);

        $categoryLinkFieldValue = (int)$connection->fetchOne(
            $connection->select()
                ->from(['e' => $categoryEntityTable], [$linkField])
                ->where('e.entity_id = ?', self::CATEGORY_ID)
        );

        $urlPathAttributeId = (int)$connection->fetchOne(
            $connection->select()
                ->from(['a' => $this->resourceConnection->getTableName('eav_attribute')], ['attribute_id'])
                ->join(
                    ['et' => $this->resourceConnection->getTableName('eav_entity_type')],
                    'a.entity_type_id = et.entity_type_id',
                    []
                )
                ->where('et.entity_table = ?', 'catalog_category_entity')
                ->where('a.attribute_code = ?', 'url_path')
        );

        $varcharTable = $this->resourceConnection->getTableName('catalog_category_entity_varchar');

        $connection->insertOnDuplicate($varcharTable, [
            $linkField => $categoryLinkFieldValue,
            'attribute_id' => $urlPathAttributeId,
            'store_id' => $secondStoreId,
            'value' => self::CATEGORY_URL_KEY,
        ], ['value']);

        // Remove admin-store url_path to simulate AC behavior after a category move
        $connection->delete($varcharTable, [
            $linkField . ' = ?' => $categoryLinkFieldValue,
            'attribute_id = ?' => $urlPathAttributeId,
            'store_id = ?' => 0,
        ]);

        return new DataObject(['sku' => self::PRODUCT_SKU, 'category_id' => self::CATEGORY_ID]);
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

        try {
            $category = $this->categoryRepository->get(self::CATEGORY_ID);
            $this->categoryRepository->delete($category);
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
}
