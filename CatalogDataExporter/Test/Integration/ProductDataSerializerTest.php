<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Integration;

use Magento\CatalogDataExporter\Model\Indexer\ProductDataSerializer;
use Magento\DataExporter\Model\FeedExportStatus;
use Magento\DataExporter\Model\FeedInterface;
use Magento\DataExporter\Model\FeedPool;
use Magento\DataExporter\Model\Indexer\DataSerializer;
use Magento\DataExporter\Status\ExportStatusCodeProvider;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\DataExporter\Model\FeedExportStatusBuilder;

class ProductDataSerializerTest extends AbstractProductTestHelper
{
    /**
     * @var DataSerializer
     */
    private $testUnit;

    /**
     * @var FeedInterface
     */
    private $productFeed;

    /**
     * @var FeedExportStatusBuilder
     */
    private $feedExportStatusBuilder;

    /**
     * @param string|null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct(
        ?string $name = null,
        array   $data = [],
        $dataName = ''
    ) {
        parent::__construct($name, $data, $dataName);
        $this->testUnit = Bootstrap::getObjectManager()->create(
            ProductDataSerializer::class // @phpstan-ignore-line
        );
        $this->productFeed = Bootstrap::getObjectManager()->get(FeedPool::class)->getFeed('products');
        $this->feedExportStatusBuilder = Bootstrap::getObjectManager()->get(FeedExportStatusBuilder::class);
    }

    /**
     * @return void
     */
    public function testSecondFeedItemHasErrorInExportStatus(): void
    {
        $feedItems = [
            [
                'feed' => [
                    'sku' => 'valid-sku1',
                    'storeViewCode' => 'default',
                    'storeCode' => 'main_website_store',
                    'websiteCode' => 'base',
                    'productId' => 1,
                    'deleted' => false,
                    'modifiedAt' => "2023-01-06 23:36:51"
                ],
                'hash' => 'hash',
            ],
            [
                'feed' => [
                    'sku' => 'wrong-data-in-feed',
                    'storeViewCode' => 'default',
                    'storeCode' => 'main_website_store',
                    'websiteCode' => 'base',
                    'productId' => 2,
                    'deleted' => false,
                    'modifiedAt' => "2023-01-06 23:36:51"
                ],
                'hash' => 'hash',
            ],
            [
                'feed' => [
                    'sku' => 'valid-sku2',
                    'storeViewCode' => 'default',
                    'storeCode' => 'main_website_store',
                    'websiteCode' => 'base',
                    'productId' => 3,
                    'deleted' => false,
                    'modifiedAt' => "2023-01-06 23:36:51"
                ],
                'hash' => 'hash',
            ],
        ];

        $exportStatus = $this->feedExportStatusBuilder->build(
            200,
            'Failed to save feed',
            [
                // only 2nd item failed
                1 => [
                    'message' => 'SKU "wrong-data-in-feed" not processed',
                    'field' => "sku"
                ],
            ]
        );

        $this->assertEquals(
            $this->prepareExpectedData($feedItems, $exportStatus, 1),
            $this->testUnit->serialize($feedItems, $exportStatus, $this->productFeed->getFeedMetadata())
        );
    }

    /**
     * @return void
     */
    public function testAllItemsHaveErrorsInExportStatus(): void
    {
        $feedItems = [
            [
                'feed' => [
                    'sku' => 'sku1',
                    'storeViewCode' => 'default',
                    'storeCode' => 'main_website_store',
                    'websiteCode' => 'base',
                    'productId' => 1,
                    'deleted' => false,
                    'modifiedAt' => "2023-01-06 23:36:51"
                ],
                'hash' => 'hash',
            ],
            [
                'feed' => [
                    'sku' => 'sku2',
                    'storeViewCode' => 'default',
                    'storeCode' => 'main_website_store',
                    'websiteCode' => 'base',
                    'productId' => 2,
                    'deleted' => false,
                    'modifiedAt' => "2023-01-06 23:36:51"
                ],
                'hash' => 'hash',
            ],
            [
                'feed' => [
                    'sku' => 'sku3',
                    'storeViewCode' => 'default',
                    'storeCode' => 'main_website_store',
                    'websiteCode' => 'base',
                    'productId' => 3,
                    'deleted' => false,
                    'modifiedAt' => "2023-01-06 23:36:51"
                ],
                'hash' => 'hash',
            ],
        ];

        $exportStatus = $this->feedExportStatusBuilder->build(
            501,
            'Failed to save feed'
        );

        $this->assertEquals(
            $this->prepareExpectedData($feedItems, $exportStatus),
            $this->testUnit->serialize($feedItems, $exportStatus, $this->productFeed->getFeedMetadata())
        );
    }

    /**
     * @param array $feedItems
     * @param FeedExportStatus $exportStatus
     * @param $failedSkuPosition
     * @return array
     */
    private function prepareExpectedData(
        array $feedItems,
        FeedExportStatus $exportStatus,
        $failedSkuPosition = null
    ): array {
        $expected = [];
        $failedStatus = $failedSkuPosition ? $exportStatus->getFailedItems()[$failedSkuPosition] : null;
        $status = $exportStatus->getStatus()->getValue();
        foreach ($feedItems as $position => $item) {
            $feed = $item['feed'];
            $expected[] = [
                'sku' => $feed['sku'],
                'id' => $feed['productId'],
                'store_view_code' => $feed['storeViewCode'],
                'is_deleted' => $feed['deleted'],
                'status' => $failedSkuPosition
                    ? ($failedSkuPosition === $position ? ExportStatusCodeProvider::FAILED_ITEM_ERROR : $status)
                    : $status,
                'modified_at' => $feed['modifiedAt'],
                'errors' => $failedSkuPosition
                    ? ($failedSkuPosition === $position ? $failedStatus['message'] : '')
                    : $exportStatus->getReasonPhrase(),
                'feed_data' => $this->jsonSerializer->serialize($feed),
                'feed_hash' => $item['hash']
            ];
        }
        return $expected;
    }
}
