<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\ProductReviewSummaryAttributes\Test\Integration\Unit\Plugin;

use Magento\DataExporter\Export\Processor;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use AdobeCommerce\ProductReviewSummaryAttributes\Api\ReviewSummarySourceInterface;
use AdobeCommerce\ProductReviewSummaryAttributes\Model\ReviewAttributeContract;
use AdobeCommerce\ProductReviewSummaryAttributes\Model\ReviewSummaryAttributeRegistry;
use AdobeCommerce\ProductReviewSummaryAttributes\Plugin\AddReviewAttributesToProductFeed;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddReviewAttributesToProductFeedTest extends TestCase
{
    /** @var ReviewSummarySourceInterface&MockObject */
    private ReviewSummarySourceInterface $source;

    /** @var ReviewSummaryAttributeRegistry&MockObject */
    private ReviewSummaryAttributeRegistry $registry;

    /** @var CommerceDataExportLoggerInterface&MockObject */
    private CommerceDataExportLoggerInterface $logger;

    /** @var Processor&MockObject */
    private Processor $processor;

    /** @var AddReviewAttributesToProductFeed */
    private AddReviewAttributesToProductFeed $plugin;

    protected function setUp(): void
    {
        $this->source = $this->createMock(ReviewSummarySourceInterface::class);
        $this->registry = $this->createMock(ReviewSummaryAttributeRegistry::class);
        $this->logger = $this->createMock(CommerceDataExportLoggerInterface::class);
        $this->processor = $this->createMock(Processor::class);
        $this->plugin = new AddReviewAttributesToProductFeed(
            $this->source,
            $this->registry,
            new ReviewAttributeContract(),
            $this->logger
        );
    }

    public function testOtherFeedsAreNotChanged(): void
    {
        $items = [['id' => 1]];
        $this->source->expects($this->never())->method('isEnabled');
        $this->registry->expects($this->never())->method('getAttributeCodes');

        $this->assertSame($items, $this->plugin->afterProcess($this->processor, $items, 'prices'));
    }

    public function testProductsFeedIsNotChangedWhenSourceIsDisabled(): void
    {
        $items = [$this->product(1, 'default')];
        $this->source->method('isEnabled')->willReturn(false);
        $this->source->expects($this->never())->method('getSummaries');
        $this->registry->expects($this->never())->method('isRegistered');
        $this->registry->expects($this->never())->method('register');

        $this->assertSame($items, $this->plugin->afterProcess($this->processor, $items, 'products'));
    }

    public function testProductsFeedIsNotChangedWhenNoSummaries(): void
    {
        $items = [$this->product(1, 'default')];
        $this->source->method('isEnabled')->willReturn(true);
        $this->source->expects($this->once())
            ->method('getSummaries')
            ->with([1], ['default'])
            ->willReturn([]);
        $this->registry->expects($this->never())->method('isRegistered');
        $this->registry->expects($this->never())->method('register');

        $this->assertSame($items, $this->plugin->afterProcess($this->processor, $items, 'products'));
    }

    public function testAttributesAreNotCreatedWithoutApprovedReviews(): void
    {
        $items = [$this->product(1, 'default')];
        $this->source->method('isEnabled')->willReturn(true);
        $this->source->method('getSummaries')->willReturn([
            '1-default' => ['reviewCount' => 0, 'ratingSummary' => 0.0],
        ]);
        $this->registry->method('isRegistered')->willReturn(false);
        $this->registry->expects($this->never())->method('register');

        $this->assertSame($items, $this->plugin->afterProcess($this->processor, $items, 'products'));
    }

    public function testAttributesAreCreatedOnFirstApprovedReview(): void
    {
        $items = [$this->product(1, 'default')];
        $this->source->method('isEnabled')->willReturn(true);
        $this->source->method('getSummaries')->willReturn([
            '1-default' => ['reviewCount' => 3, 'ratingSummary' => 80.0],
        ]);
        $this->registry->method('isRegistered')->willReturnOnConsecutiveCalls(false, true);
        $this->registry->expects($this->once())->method('register');

        $result = $this->plugin->afterProcess($this->processor, $items, 'products');

        $this->assertSame(
            [
                ['attributeCode' => 'ac_review_count', 'value' => ['3']],
                ['attributeCode' => 'ac_rating_summary', 'value' => ['80']],
            ],
            $result[0]['attributes']
        );
    }

    public function testValuesAreNotAddedWhenAttributeCreationFails(): void
    {
        $items = [$this->product(1, 'default')];
        $this->source->method('isEnabled')->willReturn(true);
        $this->source->method('getSummaries')->willReturn([
            '1-default' => ['reviewCount' => 3, 'ratingSummary' => 80.0],
        ]);
        $this->registry->method('isRegistered')->willReturn(false);
        $this->registry->expects($this->once())->method('register');
        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringStartsWith('CDE01-26 Review summary attributes are not registered'));

        $this->assertSame($items, $this->plugin->afterProcess($this->processor, $items, 'products'));
    }

    public function testValuesAreAddedOnlyToProductsWithSummary(): void
    {
        $existingAttribute = ['attributeCode' => 'color', 'value' => ['red']];
        $items = [
            $this->product(1, 'default', [$existingAttribute]),
            $this->product(1, 'second'),
            $this->product(2, 'default'),
            $this->product(3, 'default'),
        ];
        $this->source->method('isEnabled')->willReturn(true);
        $this->source->expects($this->once())
            ->method('getSummaries')
            ->with([1, 2, 3], ['default', 'second'])
            ->willReturn([
                '1-default' => ['reviewCount' => 2, 'ratingSummary' => 83.3333],
                '2-default' => ['reviewCount' => 0, 'ratingSummary' => 0.0],
            ]);
        $this->registry->method('isRegistered')->willReturn(true);
        $this->registry->expects($this->never())->method('register');

        $result = $this->plugin->afterProcess($this->processor, $items, 'products');

        $this->assertSame(
            [
                $existingAttribute,
                ['attributeCode' => 'ac_review_count', 'value' => ['2']],
                ['attributeCode' => 'ac_rating_summary', 'value' => ['83.33']],
            ],
            $result[0]['attributes']
        );
        $this->assertSame($items[1], $result[1]);
        $this->assertSame(
            [
                ['attributeCode' => 'ac_review_count', 'value' => ['0']],
                ['attributeCode' => 'ac_rating_summary', 'value' => ['0']],
            ],
            $result[2]['attributes']
        );
        $this->assertSame($items[3], $result[3]);
    }

    public function testExistingReviewAttributeValuesAreReplaced(): void
    {
        $items = [
            $this->product(1, 'default', [
                ['attributeCode' => 'ac_review_count', 'value' => ['99']],
                ['attributeCode' => 'color', 'value' => ['red']],
            ]),
        ];
        $this->source->method('isEnabled')->willReturn(true);
        $this->source->method('getSummaries')->willReturn([
            '1-default' => ['reviewCount' => 1, 'ratingSummary' => 100],
        ]);
        $this->registry->method('isRegistered')->willReturn(true);

        $result = $this->plugin->afterProcess($this->processor, $items, 'products');

        $this->assertSame(
            [
                ['attributeCode' => 'color', 'value' => ['red']],
                ['attributeCode' => 'ac_review_count', 'value' => ['1']],
                ['attributeCode' => 'ac_rating_summary', 'value' => ['100']],
            ],
            $result[0]['attributes']
        );
    }

    public function testErrorIsLoggedAndRethrown(): void
    {
        $items = [$this->product(1, 'default')];
        $exception = new \RuntimeException('DB is down');
        $this->source->method('isEnabled')->willReturn(true);
        $this->source->method('getSummaries')->willThrowException($exception);
        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'CDE01-24 Unable to add review summary data to feed',
                $this->callback(
                    static fn (array $context) => $context['feed'] === 'products' && $context['error'] === 'DB is down'
                )
            );

        $this->expectExceptionObject($exception);

        $this->plugin->afterProcess($this->processor, $items, 'products');
    }

    public function testProductAttributesFeedOverridesAreApplied(): void
    {
        $items = [
            ['attributeCode' => 'ac_review_count', 'visible' => true, 'visibleInListing' => false],
            ['attributeCode' => 'ac_rating_summary', 'visible' => true, 'visibleInListing' => true],
            ['attributeCode' => 'color', 'visible' => true],
        ];
        $this->registry->method('getAttributeCodes')->willReturn(['ac_review_count', 'ac_rating_summary']);
        $this->registry->method('getExporterOverride')->willReturn(['visible' => false]);
        $this->registry->method('isRegistered')->willReturn(true);
        $this->source->expects($this->never())->method('isEnabled');

        $result = $this->plugin->afterProcess($this->processor, $items, 'productAttributes');

        $this->assertSame(
            [
                ['attributeCode' => 'ac_review_count', 'visible' => false, 'visibleInListing' => false],
                ['attributeCode' => 'ac_rating_summary', 'visible' => false, 'visibleInListing' => true],
                ['attributeCode' => 'color', 'visible' => true],
            ],
            $result
        );
    }

    public function testProductAttributesFeedOverridesAreNotAppliedToMerchantAttributes(): void
    {
        $items = [['attributeCode' => 'ac_review_count', 'visible' => false]];
        $this->registry->method('getAttributeCodes')->willReturn(['ac_review_count', 'ac_rating_summary']);
        $this->registry->method('isRegistered')->willReturn(false);
        $this->registry->expects($this->never())->method('getExporterOverride');

        $this->assertSame($items, $this->plugin->afterProcess($this->processor, $items, 'productAttributes'));
    }

    public function testProductAttributesFeedWithoutReviewAttributesIsNotChecked(): void
    {
        $items = [['attributeCode' => 'color', 'visible' => true]];
        $this->registry->method('getAttributeCodes')->willReturn(['ac_review_count', 'ac_rating_summary']);
        $this->registry->expects($this->never())->method('isRegistered');

        $this->assertSame($items, $this->plugin->afterProcess($this->processor, $items, 'productAttributes'));
    }

    /**
     * @param int $productId
     * @param string $storeViewCode
     * @param array $attributes
     * @return array
     */
    private function product(int $productId, string $storeViewCode, array $attributes = []): array
    {
        $product = [
            'productId' => (string)$productId,
            'storeViewCode' => $storeViewCode,
            'sku' => 'sku-' . $productId,
        ];
        if ($attributes) {
            $product['attributes'] = $attributes;
        }

        return $product;
    }
}
