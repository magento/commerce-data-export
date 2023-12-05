<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Integration;

use Magento\Catalog\Model\Product\Action;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test class for simple product export with unassigned websites
 */
class SimpleProductsWebsiteUnassignTest extends AbstractProductTestHelper
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var Action
     */
    private $action;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->action = $this->objectManager->create(Action::class);
        parent::setUp();
    }

    /**
     * Check simple product status on website unassignment
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_simple_products_with_multiple_websites.php
     *
     * @param array $testData
     * @return void
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     * @throws \Zend_Db_Statement_Exception
     * @dataProvider unassignWebsitesDataProvider
     */
    public function testSimpleProductsOnSave(array $testData) : void
    {
        /** @var WebsiteRepositoryInterface $websiteRepository */
        $websiteRepository = $this->objectManager->get(WebsiteRepositoryInterface::class);
        foreach ($testData as $productData) {
            $product = $this->productRepository->get($productData['sku']);

            $websiteIds = [];
            foreach ($productData['websites'] as $websiteCode) {
                $websiteIds[] = $websiteRepository->get($websiteCode)->getId();
            }
            $product->setWebsiteIds($websiteIds);
            $this->productRepository->save($product);

            $this->emulateCustomersBehaviorAfterDeleteAction();
            $this->emulatePartialReindexBehavior([$product->getId()]);

            foreach ($productData['expected_data'] as $storeViewCode => $isDeleted) {
                $extractedProduct = $this->getExtractedProduct($productData['sku'], $storeViewCode);
                self::assertEquals(
                    $isDeleted,
                    $extractedProduct['is_deleted'],
                    "Product {$productData['sku']} is not deleted in store view {$storeViewCode}"
                );
            }
        }
    }

    /**
     * Check simple product status on website unassignment (bulk update)
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_simple_products_with_multiple_websites.php
     *
     * @param array $skus
     * @param array $websitesToUnassign
     * @param array $expectedData
     * @return void
     * @throws NoSuchEntityException
     * @throws \Zend_Db_Statement_Exception
     * @dataProvider bulkUnassignWebsitesDataProvider
     */
    public function testSimpleProductsOnBulkUpdate(array $skus, array $websitesToUnassign, array $expectedData) : void
    {
        /** @var WebsiteRepositoryInterface $websiteRepository */
        $websiteRepository = $this->objectManager->get(WebsiteRepositoryInterface::class);
        $websiteIds = [];
        foreach ($websitesToUnassign as $websiteToUnassign) {
            $websiteIds[] = $websiteRepository->get($websiteToUnassign)->getId();

        }
        $productIds = [];
        foreach ($skus as $skuToUnassign) {
            $product = $this->productRepository->get($skuToUnassign, false);
            $productIds[] = $product->getId();
        }

        $this->action->updateWebsites($productIds, $websiteIds, 'remove');

        $this->emulateCustomersBehaviorAfterDeleteAction();
        $this->emulatePartialReindexBehavior($productIds);

        foreach ($expectedData as $storeViewCode => $isDeleted) {
            foreach ($skus as $sku) {
                $extractedProduct = $this->getExtractedProduct($sku, $storeViewCode);
                self::assertEquals(
                    $isDeleted,
                    $extractedProduct['is_deleted'],
                    "Product {$sku} is not deleted in store view {$storeViewCode}"
                );
            }
        }
    }

    /**
     * @return array
     */
    public function unassignWebsitesDataProvider(): array
    {
        return [
            [
                [
                    [
                        'sku' => 'simple1',
                        'websites' => ['base'],
                        'expected_data' => [
                            'default' => "0",
                            'custom_store_view_one' => "0",
                            'custom_store_view_two' => "1"
                        ]
                    ],
                    [
                        'sku' => 'simple2',
                        'websites' => ['test'],
                        'expected_data' => [
                            'default' => "1",
                            'custom_store_view_one' => "1",
                            'custom_store_view_two' => "0"
                        ]
                    ],
                    [
                        'sku' => 'simple3',
                        'websites' => [],
                        'expected_data' => [
                            'default' => "1",
                            'custom_store_view_one' => "1",
                            'custom_store_view_two' => "1"
                        ]
                    ],
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function bulkUnassignWebsitesDataProvider(): array
    {
        return [
            [
                'skus' => [
                    'simple1',
                    'simple2',
                    'simple3'
                ],
                'websites' => [
                    'test'
                ],
                'expected_data' => [
                    'default' => "0",
                    'custom_store_view_one' => "0",
                    'custom_store_view_two' => "1"
                ]
            ],
            [
                'skus' => [
                    'simple1',
                    'simple2',
                    'simple3'
                ],
                'websites' => [
                    'base'
                ],
                'expected_data' => [
                    'default' => "1",
                    'custom_store_view_one' => "1",
                    'custom_store_view_two' => "0"
                ]
            ],
            [
                'skus' => [
                    'simple1',
                    'simple2',
                    'simple3'
                ],
                'websites' => [
                    'base',
                    'test'
                ],
                'expected_data' => [
                    'default' => "1",
                    'custom_store_view_one' => "1",
                    'custom_store_view_two' => "1"
                ]
            ]
        ];
    }
}
