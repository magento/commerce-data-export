<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Test\Integration;

class BundleProductPriceExportTest extends AbstractProductPriceExportTest
{

    /**
     * Test bundle product price export data
     *
     * @param array $priceEvents
     * @return void
     * @throws \Magento\DataExporter\Exception\UnableRetrieveData
     * @throws \Magento\Framework\Exception\LocalizedException
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Bundle/_files/bundle_product_with_dynamic_price.php
     * @dataProvider priceExportDataProvider
     */
    public function testBundleProductPriceExport(array $priceEvents)
    {
        $bundleId = $this->productRepository->get('bundle_product_with_dynamic_price')->getId();
        $events = $this->retrieveEvents(['bundle_variation']);
        self::assertTrue(isset($events[0][0]['data']));
        foreach ($priceEvents[0][0]['data'] as &$data) {
            $data['id'] = $bundleId;
        }
        self::assertEquals($priceEvents, $events);
    }

    /**
     * Bundle product test data provider
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
                                    'event_type' => 'variation_changed',
                                    'website' => null,
                                    'customer_group' => null,
                            ],
                            'data' => [
                                0 => [
                                    'id' => null,
                                    'child_id' => '10',
                                    'price_type' => 'bundle',
                                ],
                                1 => [
                                    'id' => null,
                                    'child_id' => '11',
                                    'price_type' => 'bundle',
                                ],
                            ],
                        ],
                    ],
                ]
            ]
        ];
    }
}
