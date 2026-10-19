<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\ConfigurableProductDataExporter\Test\Integration;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Action;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\CatalogDataExporter\Test\Integration\AbstractProductTestHelper;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProductDataExporter\Model\Provider\Product\ConfigurableOptionValueUid;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\Attributes\DataProvider;
use Throwable;
use Zend_Db_Statement_Exception;
use function usort;

/**
 * Test for configurable product export
 *
 */
class ConfigurableProductsTest extends AbstractProductTestHelper
{
    /**
     * @var Configurable
     */
    private $configurable;

    /**
     * @var ConfigurableOptionValueUid
     */
    private $optionValueUid;

    /**
     * Setup tests
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->configurable = Bootstrap::getObjectManager()->create(Configurable::class);
        $this->optionValueUid = Bootstrap::getObjectManager()->create(ConfigurableOptionValueUid::class);
    }

    /**
     * Validate configurable product data
     * @magentoDataFixture Magento_ConfigurableProductDataExporter::Test/_files/setup_configurable_products.php
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws Zend_Db_Statement_Exception
     * @throws Throwable
     */
    public function testConfigurableProducts() : void
    {
        $skus = ['configurable1'];
        $storeViewCodes = ['default', 'fixture_second_store'];

        foreach ($skus as $sku) {
            $product = $this->productRepository->get($sku);
            $product->setTypeInstance(Bootstrap::getObjectManager()->create(Configurable::class));

            foreach ($storeViewCodes as $storeViewCode) {
                $extractedProduct = $this->getExtractedProduct($sku, $storeViewCode);
                $this->validateBaseProductData($product, $extractedProduct, $storeViewCode);
                $this->validateRealProductData($product, $extractedProduct);
                $this->validateCategoryData($product, $extractedProduct, $storeViewCode);
                $this->validatePricingData($extractedProduct);
                $this->validateImageUrls($product, $extractedProduct);
                $this->validateAttributeData($product, $extractedProduct);
                $this->validateOptionsData($product, $extractedProduct);
            }
        }
    }

    /**
     * Validate configurable product data
     * @magentoDataFixture Magento_ConfigurableProductDataExporter::Test/_files/setup_configurable_products_with_virtual_options.php
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws Zend_Db_Statement_Exception
     * @throws Throwable
     */
    public function testConfigurableProductsWithVirtualOptions() : void
    {
        $skus = ['configurable1'];
        $storeViewCodes = ['default', 'fixture_second_store'];

        foreach ($skus as $sku) {
            $product = $this->productRepository->get($sku);
            $product->setTypeInstance(Bootstrap::getObjectManager()->create(Configurable::class));

            foreach ($storeViewCodes as $storeViewCode) {
                $extractedProduct = $this->getExtractedProduct($sku, $storeViewCode);
                $this->validateBaseProductData($product, $extractedProduct, $storeViewCode);
                $this->validateRealProductData($product, $extractedProduct);
                $this->validateCategoryData($product, $extractedProduct, $storeViewCode);
                $this->validatePricingData($extractedProduct);
                $this->validateImageUrls($product, $extractedProduct);
                $this->validateAttributeData($product, $extractedProduct);
                $this->validateOptionsData($product, $extractedProduct);
            }
        }
    }

    /**
     * Validate configurable product data
     * @magentoDataFixture Magento_ConfigurableProductDataExporter::Test/_files/setup_configurable_products.php
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @param array $outOfStockSkus
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Zend_Db_Statement_Exception
     * @dataProvider outOfStockProducts
     */
    #[DataProvider('outOfStockProducts')]
    public function testConfigurableProductsWithOutOfStockChilds(array $outOfStockSkus) : void
    {
        foreach ($outOfStockSkus as $sku) {
            $outOfStockProduct = $this->productRepository->get($sku);
            $extendedAttributes = $outOfStockProduct->getExtensionAttributes();
            $stockItem = $extendedAttributes->getStockItem();
            $stockItem->setQty(0);
            $stockItem->setIsInStock(false);
            $extendedAttributes->setStockItem($stockItem);
            $outOfStockProduct->setExtensionAttributes($extendedAttributes);
            $outOfStockProduct->save();
        }

        $skus = ['configurable1'];
        $storeViewCodes = ['default', 'fixture_second_store'];

        foreach ($skus as $sku) {
            $product = $this->productRepository->get($sku);
            $product->setTypeInstance(Bootstrap::getObjectManager()->create(Configurable::class));

            foreach ($storeViewCodes as $storeViewCode) {
                $extractedProduct = $this->getExtractedProduct($sku, $storeViewCode);
                $this->validateBaseProductData($product, $extractedProduct, $storeViewCode);
                $this->validateRealProductData($product, $extractedProduct);
                $this->validateCategoryData($product, $extractedProduct, $storeViewCode);
                $this->validatePricingData($extractedProduct);
                $this->validateImageUrls($product, $extractedProduct);
                $this->validateAttributeData($product, $extractedProduct);
                $this->validateOptionsData($product, $extractedProduct);
            }
        }
    }

    /**
     * Validate configurable product data
     * @magentoDataFixture Magento_ConfigurableProductDataExporter::Test/_files/setup_configurable_products_with_virtual_options.php
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @param array $outOfStockSkus
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Zend_Db_Statement_Exception
     * @dataProvider outOfStockVirtualProducts
     */
    #[DataProvider('outOfStockVirtualProducts')]
    public function testConfigurableProductsWithOutOfStockVirtualChilds(array $outOfStockSkus) : void
    {
        foreach ($outOfStockSkus as $sku) {
            $outOfStockProduct = $this->productRepository->get($sku);
            $extendedAttributes = $outOfStockProduct->getExtensionAttributes();
            $stockItem = $extendedAttributes->getStockItem();
            $stockItem->setQty(0);
            $stockItem->setIsInStock(false);
            $extendedAttributes->setStockItem($stockItem);
            $outOfStockProduct->setExtensionAttributes($extendedAttributes);
            $outOfStockProduct->save();
        }

        $skus = ['configurable1'];
        $storeViewCodes = ['default', 'fixture_second_store'];

        foreach ($skus as $sku) {
            $product = $this->productRepository->get($sku);
            $product->setTypeInstance(Bootstrap::getObjectManager()->create(Configurable::class));

            foreach ($storeViewCodes as $storeViewCode) {
                $extractedProduct = $this->getExtractedProduct($sku, $storeViewCode);
                $this->validateBaseProductData($product, $extractedProduct, $storeViewCode);
                $this->validateRealProductData($product, $extractedProduct);
                $this->validateCategoryData($product, $extractedProduct, $storeViewCode);
                $this->validatePricingData($extractedProduct);
                $this->validateImageUrls($product, $extractedProduct);
                $this->validateAttributeData($product, $extractedProduct);
                $this->validateOptionsData($product, $extractedProduct);
            }
        }
    }

    /**
     * Validate parent product data
     *
     * @magentoDataFixture Magento_ConfigurableProductDataExporter::Test/_files/setup_configurable_products.php
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws Zend_Db_Statement_Exception
     * @throws Throwable
     */
    public function testParentProducts() : void
    {
        $skus = ['simple_option_50', 'simple_option_60', 'simple_option_70'];
        $storeViewCodes = ['default', 'fixture_second_store'];

        foreach ($skus as $sku) {
            $product = $this->productRepository->get($sku);
            $product->setTypeInstance(Bootstrap::getObjectManager()->create(Configurable::class));

            foreach ($storeViewCodes as $storeViewCode) {
                $extractedProduct = $this->getExtractedProduct($sku, $storeViewCode);
                $this->validateParentData($product, $extractedProduct);
            }
        }
    }

    /**
     * Validate parent product data
     *
     * @magentoDataFixture Magento_ConfigurableProductDataExporter::Test/_files/setup_configurable_products.php
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws Zend_Db_Statement_Exception
     * @throws Throwable
     */
    public function testParentProductsAfterUpdate() : void
    {
        $expectedOptions = [
            'first_test_configurable' => [
                'Option 1',
            ],
            'second_test_configurable' => [
                'Option 1',
                'Option 2',
                'Option 3',
            ],
        ];
        $parentSku = 'configurable1';
        $products = [
            'simple_option_50' => [
                'disable' => false
            ],
            'simple_option_60' => [
                'disable' => true
            ],
            'simple_option_70' => [
                'disable' => true
            ]
        ];
        $storeViewCodes = ['default', 'fixture_second_store'];
        $productAction = Bootstrap::getObjectManager()->get(Action::class);

        foreach ($products as $sku => $actions) {
            $product = $this->productRepository->get($sku, true);
            if ($actions['disable'] === true) {
                $productAction->updateAttributes(
                    [$product->getEntityId()],
                    [ProductAttributeInterface::CODE_STATUS => Status::STATUS_DISABLED],
                    $product->getStoreId()
                );
            }

            $this->partialReindex([$product->getId()]);
            $product->setTypeInstance(Bootstrap::getObjectManager()->create(Configurable::class));

            foreach ($storeViewCodes as $storeViewCode) {
                $extractedOption = $this->getExtractedProduct($sku, $storeViewCode);
                $this->validateParentData($product, $extractedOption);
            }
        }
        foreach ($storeViewCodes as $storeViewCode) {
            $extractedParent = $this->getExtractedProduct($parentSku, $storeViewCode);
            $feedData = $extractedParent['feedData'];
            foreach ($feedData['optionsV2'] as $option) {
                $this->assertCount(count($expectedOptions[$option['id']]), $option['values']);
                foreach ($option['values'] as $index => $value) {
                    $this->assertEquals($expectedOptions[$option['id']][$index], $value['label']);
                }
            }
        }
    }

    /**
     * A child disabled for one website must be filtered out of the configurable options only for that website.
     *
     * `status` is a website-scoped attribute, so it cannot differ between two store views of the same website.
     * To prove per-store-view isolation of the batched possible-values resolver (rows keyed by storeViewCode in
     * ProductAssignedAttributeValues), the fixture adds a store view in a SECOND website ('cde_third_store') where
     * the child stays enabled. Disabling the child in the base website must remove its option value from the base
     * website's store views ('default', 'fixture_second_store') but leave it present in the second website's store
     * view - a divergence a store-key mix-up in the resolver would break. All three store views are resolved in one
     * Options::get() call (they are flushed together), so this also exercises the cross-store batching path.
     *
     * @magentoDataFixture Magento_ConfigurableProductDataExporter::Test/_files/setup_configurable_products.php
     * @magentoDataFixture Magento_ConfigurableProductDataExporter::Test/_files/second_website_store.php
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Zend_Db_Statement_Exception
     */
    public function testConfigurableOptionsStatusFilteredPerStoreView() : void
    {
        $parentSku = 'configurable1';
        // In setup_configurable_products.php children 50/60/70 map to Option 1/2/3 of first_test_configurable.
        $disabledChildSku = 'simple_option_60';
        $baseWebsiteStoreCode = 'fixture_second_store';
        $secondWebsiteStoreCode = 'cde_third_store';

        // Assign the configurable to the second website so it is extracted for that website's store view, where the
        // child is not disabled and "Option 2" must therefore remain.
        $secondWebsiteId = (int) $this->storeManager->getWebsite('cde_test_website')->getId();
        $skusInSecondWebsite = [
            $parentSku,
            'simple_option_50', 'simple_option_60', 'simple_option_70',
            'simple_option_55', 'simple_option_59', 'simple_option_65',
        ];
        // Assign the configurable and all its children to the second website via a direct link insert (a full
        // product re-save would re-validate required super attributes the fixture children only set partially).
        $productWebsiteTable = $this->resource->getTableName('catalog_product_website');
        foreach ($skusInSecondWebsite as $skuInSecondWebsite) {
            $entityId = (int) $this->productRepository->get($skuInSecondWebsite, true)->getId();
            $this->connection->insertOnDuplicate(
                $productWebsiteTable,
                ['product_id' => $entityId, 'website_id' => $secondWebsiteId]
            );
        }

        $baseWebsiteStoreId = (int) $this->storeManager->getStore($baseWebsiteStoreCode)->getId();
        $productAction = Bootstrap::getObjectManager()->get(Action::class);
        $child = $this->productRepository->get($disabledChildSku, true);
        $parent = $this->productRepository->get($parentSku, true);

        $nullValueChild = $this->productRepository->get('simple_option_70', true);
        $attributeId = (int) Bootstrap::getObjectManager()
            ->get(EavConfig::class)
            ->getAttribute('catalog_product', 'first_test_configurable')
            ->getId();
        $entityTable = $this->resource->getTableName('catalog_product_entity');
        $joinField = $this->connection->getAutoIncrementField($entityTable);
        $childLinkId = (int) $this->connection->fetchOne(
            $this->connection->select()
                ->from(['cpe' => $entityTable], [$joinField])
                ->where('cpe.entity_id = ?', $nullValueChild->getId())
        );
        $this->assertSame(
            1,
            $this->connection->update(
                $this->resource->getTableName('catalog_product_entity_int'),
                ['value' => null],
                [
                    'attribute_id = ?' => $attributeId,
                    'store_id = ?' => 0,
                    sprintf('%s = ?', $joinField) => $childLinkId,
                ]
            ),
            'Expected to set the child configurable attribute value to NULL'
        );

        // Disable the child at a base-website store. `status` is website-scoped, so this disables it for every base
        // website store view (admin value stays enabled), but not for the second website.
        $productAction->updateAttributes(
            [$child->getEntityId()],
            [ProductAttributeInterface::CODE_STATUS => Status::STATUS_DISABLED],
            $baseWebsiteStoreId
        );
        $reindexIds = [(int) $parent->getId()];
        foreach ($skusInSecondWebsite as $skuInSecondWebsite) {
            $reindexIds[] = (int) $this->productRepository->get($skuInSecondWebsite, true)->getId();
        }
        $this->partialReindex(array_values(array_unique($reindexIds)));

        // "Option 2" is disabled only for the base website; "Option 3" has a NULL child value in every store.
        $expectedLabelsPerStore = [
            'default' => ['Option 1'],
            $baseWebsiteStoreCode => ['Option 1'],
            $secondWebsiteStoreCode => ['Option 1', 'Option 2'],
        ];
        foreach ($expectedLabelsPerStore as $storeViewCode => $expectedLabels) {
            $extractedParent = $this->getExtractedProduct($parentSku, $storeViewCode);
            $firstConfigurableOption = null;
            foreach ($extractedParent['feedData']['optionsV2'] as $option) {
                if ($option['id'] === 'first_test_configurable') {
                    $firstConfigurableOption = $option;
                    break;
                }
            }
            $this->assertNotNull(
                $firstConfigurableOption,
                sprintf('first_test_configurable option missing for store view "%s"', $storeViewCode)
            );
            $labels = array_map(static fn(array $value) => $value['label'], $firstConfigurableOption['values']);
            $this->assertSame(
                $expectedLabels,
                $labels,
                sprintf('Unexpected configurable option values for store view "%s"', $storeViewCode)
            );
        }
    }

    /**
     * Validate parent product data with virtual options
     *
     * @magentoDataFixture Magento_ConfigurableProductDataExporter::Test/_files/setup_configurable_products_with_virtual_options.php
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws Zend_Db_Statement_Exception
     * @throws Throwable
     */
    public function testParentProductsWithVirtualOptions() : void
    {
        $skus = ['virtual_option_50', 'virtual_option_60', 'virtual_option_70'];
        $storeViewCodes = ['default', 'fixture_second_store'];

        foreach ($skus as $sku) {
            $product = $this->productRepository->get($sku);
            $product->setTypeInstance(Bootstrap::getObjectManager()->create(Configurable::class));

            foreach ($storeViewCodes as $storeViewCode) {
                $extractedProduct = $this->getExtractedProduct($sku, $storeViewCode);
                $this->validateParentData($product, $extractedProduct);
            }
        }
    }

    /**
     * Validate parent product data with virtual options after update
     *
     * @magentoDataFixture Magento_ConfigurableProductDataExporter::Test/_files/setup_configurable_products_with_virtual_options.php
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws Zend_Db_Statement_Exception
     * @throws Throwable
     */
    public function testParentProductsWithVirtualOptionsAfterUpdate() : void
    {
        $expectedOptions = [
            'first_test_configurable' => [
                'Option 1',
            ],
            'second_test_configurable' => [
                'Option 1',
                'Option 2',
                'Option 3',
            ],
        ];
        $parentSku = 'configurable1';
        $products = [
            'virtual_option_50' => [
                'disable' => false
            ],
            'virtual_option_60' => [
                'disable' => true
            ],
            'virtual_option_70' => [
                'disable' => true
            ]
        ];
        $storeViewCodes = ['default', 'fixture_second_store'];
        $productAction = Bootstrap::getObjectManager()->get(Action::class);

        foreach ($products as $sku => $actions) {
            $product = $this->productRepository->get($sku, true);
            if ($actions['disable'] === true) {
                $productAction->updateAttributes(
                    [$product->getEntityId()],
                    [ProductAttributeInterface::CODE_STATUS => Status::STATUS_DISABLED],
                    $product->getStoreId()
                );
            }

            $this->partialReindex([$product->getId()]);
            $product->setTypeInstance(Bootstrap::getObjectManager()->create(Configurable::class));

            foreach ($storeViewCodes as $storeViewCode) {
                $extractedOption = $this->getExtractedProduct($sku, $storeViewCode);
                $this->validateParentData($product, $extractedOption);
            }
        }
        foreach ($storeViewCodes as $storeViewCode) {
            $extractedParent = $this->getExtractedProduct($parentSku, $storeViewCode);
            $feedData = $extractedParent['feedData'];
            foreach ($feedData['optionsV2'] as $option) {
                $this->assertCount(count($expectedOptions[$option['id']]), $option['values']);
                foreach ($option['values'] as $index => $value) {
                    $this->assertEquals($expectedOptions[$option['id']][$index], $value['label']);
                }
            }
        }
    }

    /**
     * Validate parent product data assigned to different websites
     *
     * @magentoDataFixture Magento_ConfigurableProductDataExporter::Test/_files/setup_configurable_products_on_different_websites.php
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws Zend_Db_Statement_Exception
     * @throws Throwable
     */
    public function testParentProductsOnDifferentWebsites() : void
    {
        $skus = [
            'simple_option_50' => [
                'custom_store_view_one' => true,
                'custom_store_view_two' => false
            ],
            'simple_option_60' => [
                'custom_store_view_one' => true,
                'custom_store_view_two' => false
            ],
            'simple_option_70' => [
                'custom_store_view_one' => true,
                'custom_store_view_two' => false
            ],
            'simple_option_55' => [
                'custom_store_view_one' => false,
                'custom_store_view_two' => true
            ],
            'simple_option_59' => [
                'custom_store_view_one' => false,
                'custom_store_view_two' => true
            ],
            'simple_option_65' => [
                'custom_store_view_one' => false,
                'custom_store_view_two' => true
            ],
        ];

        foreach ($skus as $sku => $stores) {
            $product = $this->productRepository->get($sku);
            $product->setTypeInstance(Bootstrap::getObjectManager()->create(Configurable::class));

            foreach ($stores as $storeViewCode => $parentAssignedToStore) {
                $extractedProduct = $this->getExtractedProduct($sku, $storeViewCode);
                $this->validateParentData($product, $extractedProduct, $parentAssignedToStore);
            }
        }
    }

    /**
     * Validate parent product data assigned to different websites
     *
     * @magentoDataFixture Magento_ConfigurableProductDataExporter::Test/_files/setup_configurable_products_with_virtual_options_on_different_websites.php
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws Zend_Db_Statement_Exception
     * @throws Throwable
     */
    public function testParentProductsWithVirtualOptionsOnDifferentWebsites() : void
    {
        $skus = [
            'virtual_option_50' => [
                'custom_store_view_one' => true,
                'custom_store_view_two' => false
            ],
            'virtual_option_60' => [
                'custom_store_view_one' => true,
                'custom_store_view_two' => false
            ],
            'virtual_option_70' => [
                'custom_store_view_one' => true,
                'custom_store_view_two' => false
            ],
            'virtual_option_55' => [
                'custom_store_view_one' => false,
                'custom_store_view_two' => true
            ],
            'virtual_option_59' => [
                'custom_store_view_one' => false,
                'custom_store_view_two' => true
            ],
            'virtual_option_65' => [
                'custom_store_view_one' => false,
                'custom_store_view_two' => true
            ],
        ];

        foreach ($skus as $sku => $stores) {
            $product = $this->productRepository->get($sku);
            $product->setTypeInstance(Bootstrap::getObjectManager()->create(Configurable::class));

            foreach ($stores as $storeViewCode => $parentAssignedToStore) {
                $extractedProduct = $this->getExtractedProduct($sku, $storeViewCode);
                $this->validateParentData($product, $extractedProduct, $parentAssignedToStore);
            }
        }
    }

    /**
     * @return array[]
     */
    public static function outOfStockProducts(): array
    {
        return [
            [
                'outOfStockSkus' => [ // all_products_out_of_stock
                    'simple_option_50',
                    'simple_option_60',
                    'simple_option_70',
                    'simple_option_55',
                    'simple_option_59',
                    'simple_option_65'
                ]
            ],
            [
                'outOfStockSkus' => [ // one_option_products_out_of_stock
                    'simple_option_55',
                    'simple_option_59',
                    'simple_option_65'
                ]
            ],
            [
                'outOfStockSkus' => [ // one_product_from_option_out_of_stock
                    'simple_option_50',
                    'simple_option_70',
                    'simple_option_55',
                    'simple_option_65'
                ]
            ]
        ];
    }

    /**
     * @return array[]
     */
    public static function outOfStockVirtualProducts(): array
    {
        return [
            [
                'outOfStockSkus' => [ // all_products_out_of_stock
                    'virtual_option_50',
                    'virtual_option_60',
                    'virtual_option_70',
                    'virtual_option_55',
                    'virtual_option_59',
                    'virtual_option_65'
                ]
            ],
            [
                'outOfStockSkus' => [ // one_option_products_out_of_stock
                    'virtual_option_55',
                    'virtual_option_59',
                    'virtual_option_65'
                ]
            ],
            [
                'outOfStockSkus' => [ // one_product_from_option_out_of_stock
                    'virtual_option_50',
                    'virtual_option_70',
                    'virtual_option_55',
                    'virtual_option_65'
                ]
            ]
        ];
    }

    /**
     * Validate product's parent data
     *
     * @param ProductInterface $product
     * @param array $extractedProduct
     * @param bool $isParentAssigned
     * @return void
     * @throws NoSuchEntityException
     */
    private function validateParentData(
        ProductInterface $product,
        array $extractedProduct,
        bool $isParentAssigned = true
    ) : void {
        $parents = [];
        $parentIds = $this->configurable->getParentIdsByChild($product->getId());
        if ($isParentAssigned === false) {
            $this->assertEquals(null, $extractedProduct['feedData']['parents']);
            return;
        }
        foreach ($parentIds as $parentId) {
            $parentProduct = $this->productRepository->getById($parentId);
            $parents[] = [
                'sku' => $parentProduct->getSku(),
                'productType' => $parentProduct->getTypeId()
            ];
        }
        $this->assertEquals($parents, $extractedProduct['feedData']['parents']);
    }

    /**
     * Validate product options in extracted product data
     *
     * @param ProductInterface $product
     * @param array $extractedProduct
     * @return void
     */
    private function validateOptionsData(ProductInterface $product, array $extractedProduct) : void
    {
        $productOptions = $product->getExtensionAttributes()->getConfigurableProductOptions();
        $expectedOptions = [];
        foreach ($productOptions as $productOption) {
            $expectedOptions[$productOption['product_attribute']['attribute_code']] = [
                'id' => $productOption['product_attribute']['attribute_code'],
                'type' => 'configurable',
                'label' => 'Test Configurable',
                'sortOrder' => 0,
                'values' => $this->getOptionValues($productOption->getAttributeId(), $productOption->getOptions()),
            ];
        }
        $this->assertCount(count($expectedOptions), $extractedProduct['feedData']['optionsV2']);
        foreach ($extractedProduct['feedData']['optionsV2'] as $extractedOption) {
            $optionId = $extractedOption['id'];
            $this->assertEquals($expectedOptions[$optionId]['id'], $optionId);
            $this->assertEquals($expectedOptions[$optionId]['type'], $extractedOption['type']);
            $this->assertEquals($expectedOptions[$optionId]['label'], $extractedOption['label']);
            $this->assertEquals($expectedOptions[$optionId]['sortOrder'], $extractedOption['sortOrder']);
            $this->assertCount(count($expectedOptions[$optionId]['values']), $extractedOption['values']);

            foreach ($extractedOption['values'] as $value) {
                $valueId = $value['id'];
                $this->assertEquals($expectedOptions[$optionId]['values'][$valueId]['id'], $valueId);
                $this->assertEquals(
                    $expectedOptions[$optionId]['values'][$valueId]['label'],
                    $value['label']
                );
            }
        }
    }

    /**
     * Get partially hardcoded option values to compare to extracted product data
     *
     * @param array $optionValues
     * @return array
     */
    private function getOptionValues(string $attributeId, array $optionValues) : array
    {
        $values = [];
        $i = 1;
        foreach ($optionValues as $optionValue) {
            $id = $this->optionValueUid->resolve($attributeId, $optionValue['value_index']);
            $values[$id] = [
                'id' => $id,
                'label' => 'Option ' . $i,
            ];
            $i++;
        }
        return $values;
    }
}
