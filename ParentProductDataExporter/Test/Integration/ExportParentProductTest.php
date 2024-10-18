<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\ParentProductDataExporter\Test\Integration;

use DateTime;
use DateTimeInterface;
use Magento\CatalogDataExporter\Test\Integration\AbstractProductTestHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\DataExporter\Model\FeedInterface;
use Magento\DataExporter\Model\FeedPool;
use Magento\TestFramework\Helper\Bootstrap;
use Zend_Db_Statement_Exception;

/**
 * Check parents fields for all types of relation products
 *
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ExportParentProductTest extends AbstractProductTestHelper
{
    /**
     * @var FeedInterface
     */
    private FeedInterface $productsFeed;

    public function setUp(): void
    {
        parent::setUp();
        $this->productsFeed = Bootstrap::getObjectManager()->get(FeedPool::class)->getFeed('products');
    }

    /**
     * @magentoDataFixture Magento_ParentProductDataExporter::Test/_files/configurable_products.php
     * @dataProvider expectedSimpleConfigurableWithParentsData
     * @throws NoSuchEntityException
     * @throws Zend_Db_Statement_Exception
     */
    public function testExportSimpleConfigurableProductsWithParentData(array $expectedSimpleProduct): void
    {
        $this->checkExpectedItemsAreExportedInFeed($expectedSimpleProduct);
    }

    /**
     * @return \array[][]
     */
    private function expectedSimpleConfigurableWithParentsData(): array
    {
        return [
            [
                [
                    [
                        'sku' => 'configurable-data-parent-test-child-1',
                        'type' => 'SIMPLE',
                        'parents' => [
                            0 => ['sku' => 'configurable-data-parent-test', 'productType' => 'configurable'],
                        ],
                    ],
                ]
            ]
        ];
    }

    /**
     * @magentoDataFixture Magento_ParentProductDataExporter::Test/_files/grouped_products.php
     * @dataProvider expectedSimpleProductWithParentsData
     * @throws NoSuchEntityException
     * @throws Zend_Db_Statement_Exception
     */
    public function testExportSimpleProductsWithParentData(array $expectedSimpleProduct): void
    {
        $this->checkExpectedItemsAreExportedInFeed($expectedSimpleProduct);
    }

    /**
     * @return \array[][]
     */
    private function expectedSimpleProductWithParentsData(): array
    {
        return [
            [
                [
                    [
                        'sku' => 'simple_product_parent_product_test_1',
                        'type' => 'SIMPLE',
                        'parents' => [
                            0 => ['sku' => 'grouped-product-parent-product-test', 'productType' => 'grouped']
                        ],
                    ],
                    [
                        'sku' => 'simple_product_parent_product_test_2',
                        'type' => 'SIMPLE',
                        'parents' => [
                            0 => ['sku' => 'grouped-product-parent-product-test', 'productType' => 'grouped']
                        ],
                    ],
                    [
                        'sku' => 'simple_product_with_no_parent_test',
                        'type' => 'SIMPLE',
                        'parents' => null,
                    ],
                ]
            ]
        ];
    }

    /**
     * @param array $expectedItems
     * @return void
     * @throws NoSuchEntityException
     * @throws Zend_Db_Statement_Exception
     */
    private function checkExpectedItemsAreExportedInFeed(array $expectedItems): void
    {
        $ids = [];
        foreach ($expectedItems as $expectedItem) {
            $ids[] = $this->productRepository->get($expectedItem['sku'])->getId();
        }
        $timestamp = new DateTime('Now - 1 second');
        $this->emulatePartialReindexBehavior($ids);
        $actualProductsFeed = $this->productsFeed->getFeedSince($timestamp->format(DateTimeInterface::W3C));

        self::assertNotEmpty($actualProductsFeed['feed'], 'Product Feed should not be empty');

        foreach ($expectedItems as $expectedProduct) {
            $expectedFeedFound = false;
            foreach ($actualProductsFeed['feed'] as $productFeed) {
                if ($productFeed['sku'] === $expectedProduct['sku']) {
                    $expectedFeedFound = true;
                    self::assertEqualsCanonicalizing(
                        $expectedProduct['parents'],
                        $productFeed['parents'],
                        "Parents is not equal"
                    );
                }
            }
            if (false === $expectedFeedFound) {
                self::fail("Cannot find product price feed");
            }
        }
    }
}
