<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Test\Integration;

class ProductPriceExportTest extends AbstractProductPriceExportTest
{

    /**
     * Test normal and special product price export data
     *
     * @param array $priceEvents
     * @return void
     * @throws \Magento\DataExporter\Exception\UnableRetrieveData
     * @throws \Magento\Framework\Exception\LocalizedException
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Catalog/_files/multiple_products.php
     * @dataProvider priceExportDataProvider
     */
    public function testPriceExport(array $priceEvents)
    {
        $events = $this->retrieveEvents(['product_price']);
        self::assertEquals($priceEvents, $events);
    }

    /**
     * Product test data provider
     *
     * @return array
     */
    public function priceExportDataProvider(): array
    {
        return [
            [
                [
                    [
                        [
                            'meta' => [
                                'event_type' => 'price_changed',
                                'website' => null,
                                'customer_group' => null,
                            ],
                            'data' => [
                                0 => [
                                    'id' => '10',
                                    'attribute_code' => 'price',
                                    'value' => '10.000000',
                                ],
                                1 => [
                                    'id' => '10',
                                    'attribute_code' => 'special_price',
                                    'value' => '5.990000',
                                ],
                                2 => [
                                    'id' => '11',
                                    'attribute_code' => 'price',
                                    'value' => '20.000000',
                                ],
                                3 => [
                                    'id' => '11',
                                    'attribute_code' => 'special_price',
                                    'value' => '15.990000',
                                ],
                                4 => [
                                    'id' => '12',
                                    'attribute_code' => 'price',
                                    'value' => '30.000000',
                                ],
                                5 => [
                                    'id' => '12',
                                    'attribute_code' => 'special_price',
                                    'value' => '25.990000',
                                ],
                            ],
                        ],
                    ],
                ]
            ]
        ];
    }
}
