<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Integration;

use Magento\CatalogDataExporter\Test\Integration\AbstractProductTestHelper;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test for grouped product export
 */
class GroupedProductsTest extends AbstractProductTestHelper
{
    private const GROUPED_PRODUCT_SKU = 'grouped-product';

    /**
     * @var ArrayUtils
     */
    private $arrayUtils;

    /**
     * @inheritDoc
     */
    protected function setUp() : void
    {
        $this->arrayUtils = Bootstrap::getObjectManager()->create(ArrayUtils::class);

        parent::setUp();
    }

    /**
     * Validate grouped product options data
     *
     * @param array $groupedProductOptionsDataProvider
     *
     * @magentoDataFixture Magento/GroupedProduct/_files/product_grouped.php
     * @dataProvider getGroupedProductOptionsDataProvider
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     *
     * @return void
     */
    public function testGroupedProductOptions(array $groupedProductOptionsDataProvider) : void
    {
        $extractedProduct = $this->getExtractedProduct(self::GROUPED_PRODUCT_SKU, 'default');
        $this->assertNotEmpty($extractedProduct, 'Feed data must not be empty');

        foreach ($groupedProductOptionsDataProvider as $key => $expectedData) {
            $diff = $this->arrayUtils->recursiveDiff($expectedData, $extractedProduct[$key]);
            self::assertEquals([], $diff, 'Actual feed data doesn\'t equal to expected data');
        }
    }

    /**
     * Validate grouped product options data in multiple website
     *
     * @param array $groupedProductOptionsDataProvider
     *
     * @magentoDataFixture Magento/Store/_files/second_website_with_two_stores.php
     * @magentoDataFixture Magento/GroupedProduct/_files/product_grouped_in_multiple_websites.php
     * @dataProvider getGroupedProductOptionsDataProvider
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     *
     * @return void
     */
    public function testGroupedProductOptionsInMultipleWebsites(array $groupedProductOptionsDataProvider) : void
    {
        $storeViews = ['fixture_second_store','fixture_third_store'];

        foreach ($storeViews as $store) {
            $extractedProduct = $this->getExtractedProduct(self::GROUPED_PRODUCT_SKU, $store);
            $this->assertNotEmpty($extractedProduct, 'Feed data must not be empty');

            // Assert values are equal for fixture_second_store
            $groupedProductOptionsDataProvider['feedData']['storeViewCode'] = $store;
            foreach ($groupedProductOptionsDataProvider as $key => $expectedData) {
                $diff = $this->arrayUtils->recursiveDiff($expectedData, $extractedProduct[$key]);
                self::assertEquals([], $diff, 'Actual feed data doesn\'t equal to expected data');
            }
        }
    }

    /**
     * Get grouped product options data provider
     *
     * @return array
     */
    public function getGroupedProductOptionsDataProvider() : array
    {
        return [
            'groupedProduct' => [
                'item' => [
                    'feedData' => [
                        'sku' => self::GROUPED_PRODUCT_SKU,
                        'storeViewCode' => 'default',
                        'name' => 'Grouped Product',
                        'type' => 'grouped',
                        'optionsV2' => [
                            [
                                'type' => 'grouped',
                                'values' => [
                                    [
                                        'id' => 1,
                                        'sortOrder' => 1,
                                        'qty' => 1,
                                        'sku' => 'simple',
                                        'qtyMutability' => true,
                                    ],
                                    [
                                        'id' => 21,
                                        'sortOrder' => 2,
                                        'qty' => 2,
                                        'sku' => 'virtual-product',
                                        'qtyMutability' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
