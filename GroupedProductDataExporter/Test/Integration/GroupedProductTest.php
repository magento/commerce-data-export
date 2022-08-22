<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\BundleProductDataExporter\Test\Integration;

use Magento\CatalogDataExporter\Test\Integration\AbstractProductTestHelper;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test for grouped product export
 */
class GroupedProductTest extends AbstractProductTestHelper
{
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
        $extractedProduct = $this->getExtractedProduct('grouped-product', 'default');
        $this->assertNotEmpty($extractedProduct, 'Feed data must not be empty');

        foreach ($groupedProductOptionsDataProvider as $key => $expectedData) {
            $diff = $this->arrayUtils->recursiveDiff($expectedData, $extractedProduct[$key]);
            self::assertEquals([], $diff, 'Actual feed data doesn\'t equal to expected data');
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
                        'sku' => 'grouped-product',
                        'storeViewCode' => 'default',
                        'name' => 'Grouped Product',
                        'type' => 'grouped',
                        'optionsV2' => [
                            [
                                'type' => 'grouped',
                                'renderType' => null,
                                'required' => true,
                                'sortOrder' => 1,
                                'values' => [
                                    [
                                        'sortOrder' => 0,
                                        'label' => 'Simple Product',
                                        'id' => 1,
                                        'qty' => 1,
                                        'sku' => "simple",
                                        'isDefault' => false,
                                        'qtyMutability' => true,
                                    ],
                                    [
                                        'sortOrder' => 1,
                                        'label' => 'Virtual Product',
                                        'id' => 21,
                                        'qty' => 1,
                                        'sku' => "virtual",
                                        'isDefault' => false,
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
