<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryDataExporter\Test\Integration;

use Magento\DataExporter\Model\FeedInterface;
use Magento\DataExporter\Model\FeedPool;
use Magento\Framework\Exception\InputException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryCatalogApi\Api\BulkSourceUnassignInterface;
use Magento\InventoryCatalogApi\Model\SourceItemsProcessorInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 */
class UnassignProductFromStockTest extends TestCase
{
    /**
     * @var FeedInterface
     */
    private $stockStatusFeed;

    /**
     * @var SourceItemsProcessorInterface
     */
    private $sourceItemProcessor;

    /**
     * @var BulkSourceUnassignInterface
     */
    private $bulkSourceUnassign;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->stockStatusFeed = Bootstrap::getObjectManager()->get(FeedPool::class)->getFeed('stock_statuses');
        $this->sourceItemProcessor = Bootstrap::getObjectManager()->get(SourceItemsProcessorInterface::class);
        $this->bulkSourceUnassign = Bootstrap::getObjectManager()->get(BulkSourceUnassignInterface::class);
    }

    /**
     * @dataProvider stocksUnassignDataProvider
     * @param string $sku
     * @param array $sourcesToLeave
     * @param array $expectedData
     * @throws InputException
     * @magentoDataFixture Magento_InventoryDataExporter::Test/_files/products_with_sources.php
     */
    public function te1stSourceItemStockUnassigned(string $sku, array $sourcesToLeave, array $expectedData)
    {
        $sourceItems = $this->getSourcesData($sku, $sourcesToLeave);
        $this->sourceItemProcessor->execute($sku, $sourceItems);

        $feedData = $this->getFeedData([$sku]);

        $this->verifyResults($feedData, $sku, $expectedData);
    }

    /**
     * @dataProvider stocksBulkUnassignDataProvider
     * @param array $skus
     * @param array $sourcesToUnassign
     * @param array $expectedData
     * @throws InputException
     * @throws \Zend_Db_Statement_Exception
     * @throws \Magento\Framework\Validation\ValidationException
     * @magentoDataFixture Magento_InventoryDataExporter::Test/_files/products_with_sources.php
     */
    public function testSourceItemsBulkUnassign(array $skus, array $sourcesToUnassign, array $expectedData)
    {
        $this->bulkSourceUnassign->execute(
            $skus,
            $sourcesToUnassign
        );

        $feedData = $this->getFeedData($skus);

        foreach ($skus as $sku) {
            $this->verifyResults($feedData, $sku, $expectedData[$sku]);
        }
    }

    /**
     * @param array $skus
     * @return array[stock][sku]
     * @throws \Zend_Db_Statement_Exception
     */
    private function getFeedData(array $skus): array
    {
        $output = [];
        foreach ($this->stockStatusFeed->getFeedSince('1')['feed'] as $item) {
            if (\in_array($item['sku'], $skus, true)) {
                $output[$item['stockId']][$item['sku']] = $item;
            }
        }
        return $output;
    }

    /**
     * @return array[]
     */
    public function stocksUnassignDataProvider(): array
    {
        return [
            'one_stock_unassign' => [
                'sku' => 'product_in_EU_stock_with_2_sources',
                'sources_to_leave' => ['eu-2'],
                'expected_data' => [
                    '10' => [
                        'sku' => 'product_in_EU_stock_with_2_sources',
                        'stock_id' => 10,
                        'deleted' => false
                    ],
                    '30' => [
                        'sku' => 'product_in_EU_stock_with_2_sources',
                        'stock_id' => 30,
                        'deleted' => true
                    ]
                ]
            ],
            'unassign_sources_from_multiple_stocks' => [
                'sku' => 'product_in_Global_stock_with_3_sources',
                'sources_to_leave' => ['eu-2'],
                'expected_data' => [
                    '10' => [
                        'sku' => 'product_in_Global_stock_with_3_sources',
                        'stock_id' => 10,
                        'deleted' => false
                    ],
                    '20' => [
                        'sku' => 'product_in_Global_stock_with_3_sources',
                        'stock_id' => 20,
                        'deleted' => true
                    ],
                    '30' => [
                        'sku' => 'product_in_Global_stock_with_3_sources',
                        'stock_id' => 30,
                        'deleted' => true
                    ]
                ]
            ],
            'default_stock_unassign_from_only_default_stock_product' => [
                'sku' => 'product_with_default_stock_only',
                'sources_to_leave' => [],
                'expected_data' => [
                    '1' => [
                        'sku' => 'product_with_default_stock_only',
                        'stock_id' => 1,
                        'deleted' => true
                    ]
                ]
            ],
            'default_stock_unassign_from_default_and_custom_stocks_product' => [
                'sku' => 'product_in_default_and_2_EU_sources',
                'sources_to_leave' => ['eu1', 'eu2'],
                'expected_data' => [
                    '1' => [
                        'sku' => 'product_in_default_and_2_EU_sources',
                        'stock_id' => 1,
                        'deleted' => true
                    ],
                    '10' => [
                        'sku' => 'product_in_default_and_2_EU_sources',
                        'stock_id' => 10,
                        'deleted' => false
                    ]
                ]
            ],
            'custom_stock_unassign_from_default_and_custom_stocks_product' => [
                'sku' => 'product_in_default_and_2_EU_sources',
                'sources_to_leave' => ['default'],
                'expected_data' => [
                    '1' => [
                        'sku' => 'product_in_default_and_2_EU_sources',
                        'stock_id' => 1,
                        'deleted' => false
                    ],
                    '10' => [
                        'sku' => 'product_in_default_and_2_EU_sources',
                        'stock_id' => 10,
                        'deleted' => true
                    ]
                ]
            ],
        ];
    }

    /**
     * @return array[]
     */
    public function stocksBulkUnassignDataProvider(): array
    {
        return [
            'one_stock_unassign' => [
                'skus' => [
                    'product_in_EU_stock_with_2_sources',
                    'product_in_Global_stock_with_3_sources',
                    'product_with_default_stock_only',
                    'product_in_default_and_2_EU_sources'
                ],
                'sources_to_unassign' => ['eu-1', 'eu-2'],
                'expected_data' => [
                    'product_with_default_stock_only' => [
                        '1' => [
                            'sku' => 'product_with_default_stock_only',
                            'stock_id' => 1,
                            'deleted' => false
                        ],
                    ],
                    'product_in_default_and_2_EU_sources' => [
                        '1' => [
                            'sku' => 'product_in_default_and_2_EU_sources',
                            'stock_id' => 1,
                            'deleted' => false
                        ],
                        '10' => [
                            'sku' => 'product_in_default_and_2_EU_sources',
                            'stock_id' => 10,
                            'deleted' => true
                        ],
                    ],
                    'product_in_EU_stock_with_2_sources' => [
                        '10' => [
                            'sku' => 'product_in_EU_stock_with_2_sources',
                            'stock_id' => 10,
                            'deleted' => true
                        ],
                    ],
                    'product_in_Global_stock_with_3_sources' => [
                        '10' => [
                            'sku' => 'product_in_Global_stock_with_3_sources',
                            'stock_id' => 10,
                            'deleted' => true
                        ],
                        '30' => [
                            'sku' => 'product_in_Global_stock_with_3_sources',
                            'stock_id' => 30,
                            'deleted' => false
                        ],
                    ]
                ]
            ],
            'two_stocks_unassign' => [
                'skus' => [
                    'product_in_EU_stock_with_2_sources',
                    'product_in_Global_stock_with_3_sources',
                    'product_with_default_stock_only',
                    'product_in_default_and_2_EU_sources'
                ],
                'sources_to_unassign' => ['eu-1', 'eu-2', 'us-1'],
                'expected_data' => [
                    'product_with_default_stock_only' => [
                        '1' => [
                            'sku' => 'product_with_default_stock_only',
                            'stock_id' => 1,
                            'deleted' => false
                        ],
                    ],
                    'product_in_default_and_2_EU_sources' => [
                        '1' => [
                            'sku' => 'product_in_default_and_2_EU_sources',
                            'stock_id' => 1,
                            'deleted' => false
                        ],
                        '10' => [
                            'sku' => 'product_in_default_and_2_EU_sources',
                            'stock_id' => 10,
                            'deleted' => true
                        ],
                    ],
                    'product_in_EU_stock_with_2_sources' => [
                        '10' => [
                            'sku' => 'product_in_EU_stock_with_2_sources',
                            'stock_id' => 10,
                            'deleted' => true
                        ],
                    ],
                    'product_in_Global_stock_with_3_sources' => [
                        '10' => [
                            'sku' => 'product_in_Global_stock_with_3_sources',
                            'stock_id' => 10,
                            'deleted' => true
                        ],
                        '30' => [
                            'sku' => 'product_in_Global_stock_with_3_sources',
                            'stock_id' => 30,
                            'deleted' => true
                        ],
                    ]
                ]
            ],
            'two_stocks_and_default_unassign' => [
                'skus' => [
                    'product_in_EU_stock_with_2_sources',
                    'product_in_Global_stock_with_3_sources',
                    'product_with_default_stock_only',
                    'product_in_default_and_2_EU_sources'
                ],
                'sources_to_unassign' => ['eu-1', 'eu-2', 'us-1', 'default'],
                'expected_data' => [
                    'product_with_default_stock_only' => [
                        '1' => [
                            'sku' => 'product_with_default_stock_only',
                            'stock_id' => 1,
                            'deleted' => true
                        ],
                    ],
                    'product_in_default_and_2_EU_sources' => [
                        '1' => [
                            'sku' => 'product_in_default_and_2_EU_sources',
                            'stock_id' => 1,
                            'deleted' => true
                        ],
                        '10' => [
                            'sku' => 'product_in_default_and_2_EU_sources',
                            'stock_id' => 10,
                            'deleted' => true
                        ],
                    ],
                    'product_in_EU_stock_with_2_sources' => [
                        '10' => [
                            'sku' => 'product_in_EU_stock_with_2_sources',
                            'stock_id' => 10,
                            'deleted' => true
                        ],
                    ],
                    'product_in_Global_stock_with_3_sources' => [
                        '10' => [
                            'sku' => 'product_in_Global_stock_with_3_sources',
                            'stock_id' => 10,
                            'deleted' => true
                        ],
                        '30' => [
                            'sku' => 'product_in_Global_stock_with_3_sources',
                            'stock_id' => 30,
                            'deleted' => true
                        ],
                    ]
                ]
            ],
            'only_default_stock_unassign' => [
                'skus' => [
                    'product_in_EU_stock_with_2_sources',
                    'product_in_Global_stock_with_3_sources',
                    'product_with_default_stock_only',
                    'product_in_default_and_2_EU_sources'
                ],
                'sources_to_unassign' => ['default'],
                'expected_data' => [
                    'product_with_default_stock_only' => [
                        '1' => [
                            'sku' => 'product_with_default_stock_only',
                            'stock_id' => 1,
                            'deleted' => true
                        ],
                    ],
                    'product_in_default_and_2_EU_sources' => [
                        '1' => [
                            'sku' => 'product_in_default_and_2_EU_sources',
                            'stock_id' => 1,
                            'deleted' => true
                        ],
                        '10' => [
                            'sku' => 'product_in_default_and_2_EU_sources',
                            'stock_id' => 10,
                            'deleted' => false
                        ],
                    ],
                    'product_in_EU_stock_with_2_sources' => [
                        '10' => [
                            'sku' => 'product_in_EU_stock_with_2_sources',
                            'stock_id' => 10,
                            'deleted' => false
                        ],
                    ],
                    'product_in_Global_stock_with_3_sources' => [
                        '10' => [
                            'sku' => 'product_in_Global_stock_with_3_sources',
                            'stock_id' => 10,
                            'deleted' => false
                        ],
                        '30' => [
                            'sku' => 'product_in_Global_stock_with_3_sources',
                            'stock_id' => 30,
                            'deleted' => false
                        ],
                    ]
                ]
            ],
        ];
    }

    /**
     * @param string $sku
     * @param array $sourcesToUnassign
     * @return array
     */
    private function getSourcesData(string $sku, array $sourcesToUnassign): array
    {
        $sourcesData = [];
        foreach ($sourcesToUnassign as $sourceCode) {
            $sourcesData[] = [
                SourceItemInterface::SOURCE_CODE => $sourceCode,
                SourceItemInterface::SKU => $sku
            ];
        }

        return $sourcesData;
    }

    /**
     * @param array $feedData
     * @param string $sku
     * @param array $expectedData
     */
    private function verifyResults(array $feedData, string $sku, array $expectedData): void
    {
        foreach ($expectedData as $expectedStockId => $expectedStockData) {
            self::assertEquals(
                $expectedStockData,
                [
                    'sku' => $feedData[$expectedStockId][$sku]['sku'],
                    'stock_id' => $feedData[$expectedStockId][$sku]['stockId'],
                    'deleted' => $feedData[$expectedStockId][$sku]['deleted'],
                ]
            );
        }
    }
}
