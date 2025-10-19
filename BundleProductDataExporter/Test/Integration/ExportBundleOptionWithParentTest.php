<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\BundleProductDataExporter\Test\Integration;

use Magento\CatalogDataExporter\Test\Integration\AbstractProductTestHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\DataExporter\Model\FeedInterface;
use Magento\DataExporter\Model\FeedPool;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Zend_Db_Statement_Exception;

/**
 * Check parents fields for options bundle products
 *
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ExportBundleOptionWithParentTest extends AbstractProductTestHelper
{
    private const SIMPLE_SKU = 'simple1';
    /**
     * @var FeedInterface
     */
    private FeedInterface $productsFeed;

    /**
     * @inheritDoc
     */
    protected function setUp() : void
    {
        $this->productsFeed = Bootstrap::getObjectManager()->get(FeedPool::class)->getFeed('products');

        parent::setUp();
    }

    /**
     * @magentoDataFixture Magento/Bundle/_files/product_with_multiple_options.php
     * @magentoDataFixture Magento/Bundle/_files/bundle_product_with_dynamic_price.php
     *
     * @dataProvider expectedBundleOptionsWithParentData
     * @throws NoSuchEntityException
     * @throws Zend_Db_Statement_Exception
     */
    public function testExportBundleOptionsWithParentData(array $expectedSimpleProduct): void
    {
        $this->checkExpectedItemsAreExportedInFeed($expectedSimpleProduct);
    }

    /**
     * @return \array[][]
     */
    private function expectedBundleOptionsWithParentData(): array
    {
        return [
            [
                [
                    [
                        'sku' => self::SIMPLE_SKU,
                        'type' => 'SIMPLE',
                        'parents' => [
                            0 => ['sku' => 'bundle-product', 'productType' => 'bundle_fixed'],
                            1 => ['sku' => 'bundle_product_with_dynamic_price', 'productType' => 'bundle'],
                        ],
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
        $extractedProduct = $this->getExtractedProduct(self::SIMPLE_SKU, 'default');

        self::assertNotEmpty($extractedProduct, 'Product Feed should not be empty');

        foreach ($expectedItems as $product) {
            self::assertEquals($product['sku'], $extractedProduct['sku']);

            self::assertEqualsCanonicalizing(
                $product['parents'],
                $extractedProduct['feedData']['parents'],
                "Expected Parents are not equal to Actual"
            );
        }
    }
}
