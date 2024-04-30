<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Integration;

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
     * @var string
     */
    private const EXPECTED_DATE_TIME_FORMAT = '%d-%d-%d %d:%d:%d';

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
            \Magento\DataExporter\Model\Indexer\DataSerializer::class // @phpstan-ignore-line
        );
        $this->productFeed = Bootstrap::getObjectManager()->get(FeedPool::class)->getFeed('products');
        $this->feedExportStatusBuilder = Bootstrap::getObjectManager()->get(FeedExportStatusBuilder::class);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testSecondFeedItemHasErrorInExportStatus(): void
    {
        $metadata = $this->productFeed->getFeedMetadata();
        $modifiedAt = (new \DateTime('2023-01-06 23:36:51'))->format($metadata->getDateTimeFormat());
        $rowModifiedAt = (new \DateTime())->getTimestamp();

        $feedItems = [
            [
                'feed_data' => [
                    'sku' => 'valid-sku1',
                    'storeViewCode' => 'default',
                    'storeCode' => 'main_website_store',
                    'websiteCode' => 'base',
                    'productId' => 1,
                    'deleted' => false,
                    'modifiedAt' => $modifiedAt
                ],
                'feed_hash' => 'hash',
                'feed_id' => 'feed_id_1',
            ],
            [
                'feed_data' => [
                    'sku' => 'wrong-data-in-feed',
                    'storeViewCode' => 'default',
                    'storeCode' => 'main_website_store',
                    'websiteCode' => 'base',
                    'productId' => 2,
                    'deleted' => false,
                    'modifiedAt' => $modifiedAt
                ],
                'feed_hash' => 'hash',
                'feed_id' => 'feed_id_2',
            ],
            [
                'feed_data' => [
                    'sku' => 'valid-sku2',
                    'storeViewCode' => 'default',
                    'storeCode' => 'main_website_store',
                    'websiteCode' => 'base',
                    'productId' => 3,
                    'deleted' => false,
                    'modifiedAt' => $modifiedAt
                ],
                'feed_hash' => 'hash',
                'feed_id' => 'feed_id_3',
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
        $actual = $this->testUnit->serialize($feedItems, $exportStatus, $this->productFeed->getFeedMetadata());
        foreach ($actual as &$feed) {
            $this->assertNotEmpty($feed['modified_at']);
            $this->assertStringMatchesFormat(self::EXPECTED_DATE_TIME_FORMAT, $feed['modified_at']);
            $actualRowModifiedAt = (new \DateTime($feed['modified_at']))->getTimestamp();
            $this->assertEqualsWithDelta($rowModifiedAt, $actualRowModifiedAt, 3);
            unset($feed['modified_at']);
        }
        $this->assertEquals(
            $this->prepareExpectedData($feedItems, $exportStatus, 1),
            $actual
        );
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testAllItemsHaveErrorsInExportStatus(): void
    {
        $metadata = $this->productFeed->getFeedMetadata();
        $modifiedAt = (new \DateTime('2023-01-06 23:36:51'))->format($metadata->getDateTimeFormat());
        $rowModifiedAt = (new \DateTime())->getTimestamp();
        $feedItems = [
            [
                'feed_data' => [
                    'sku' => 'sku1',
                    'storeViewCode' => 'default',
                    'storeCode' => 'main_website_store',
                    'websiteCode' => 'base',
                    'productId' => 1,
                    'deleted' => false,
                    'modifiedAt' => $modifiedAt
                ],
                'feed_hash' => 'hash',
                'feed_id' => 'feed_id_1',
            ],
            [
                'feed_data' => [
                    'sku' => 'sku2',
                    'storeViewCode' => 'default',
                    'storeCode' => 'main_website_store',
                    'websiteCode' => 'base',
                    'productId' => 2,
                    'deleted' => false,
                    'modifiedAt' => $modifiedAt
                ],
                'feed_hash' => 'hash',
                'feed_id' => 'feed_id_2',
            ],
            [
                'feed_data' => [
                    'sku' => 'sku3',
                    'storeViewCode' => 'default',
                    'storeCode' => 'main_website_store',
                    'websiteCode' => 'base',
                    'productId' => 3,
                    'deleted' => false,
                    'modifiedAt' => $modifiedAt
                ],
                'feed_hash' => 'hash',
                'feed_id' => 'feed_id_3',
            ],
        ];

        $exportStatus = $this->feedExportStatusBuilder->build(
            501,
            'Failed to save feed'
        );
        $actual = $this->testUnit->serialize($feedItems, $exportStatus, $this->productFeed->getFeedMetadata());
        foreach ($actual as &$feed) {
            $this->assertNotEmpty($feed['modified_at']);
            $this->assertStringMatchesFormat(self::EXPECTED_DATE_TIME_FORMAT, $feed['modified_at']);
            $actualRowModifiedAt = (new \DateTime($feed['modified_at']))->getTimestamp();
            $this->assertEqualsWithDelta($rowModifiedAt, $actualRowModifiedAt, 3);
            unset($feed['modified_at']);
        }

        $this->assertEquals(
            $this->prepareExpectedData($feedItems, $exportStatus),
            $actual
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
            $feed = $item['feed_data'];
            $expected[] = [
                'is_deleted' => $feed['deleted'],
                'status' => $failedSkuPosition
                    ? ($failedSkuPosition === $position ? ExportStatusCodeProvider::FAILED_ITEM_ERROR : $status)
                    : $status,
                'errors' => $failedSkuPosition
                    ? ($failedSkuPosition === $position ? $failedStatus['message'] : '')
                    : $exportStatus->getReasonPhrase(),
                'feed_data' => $this->jsonSerializer->serialize($feed),
                'feed_hash' => $item['feed_hash'],
                'feed_id' => $item['feed_id'],
                'source_entity_id' => $feed['productId']
            ];
        }
        return $expected;
    }
}
