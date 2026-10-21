<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\ProductReviewSummarySync\Test\Integration\Unit\Model;

use AdobeCommerce\ProductReviewSummaryAttributes\Model\ReviewAttributeContract;
use AdobeCommerce\ProductReviewSummarySync\Model\Query\ReviewSummaryQuery;
use AdobeCommerce\ProductReviewSummarySync\Model\ReviewSummarySource;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReviewSummarySourceTest extends TestCase
{
    /**
     * @var AdapterInterface&MockObject
     */
    private AdapterInterface $connection;

    /**
     * @var ReviewSummaryQuery&MockObject
     */
    private ReviewSummaryQuery $query;

    /**
     * @var array<int, bool> store id => catalog/review/active
     */
    private array $reviewsActive = [];

    /**
     * @var ReviewSummarySource
     */
    private ReviewSummarySource $source;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(AdapterInterface::class);
        $resourceConnection = $this->createMock(ResourceConnection::class);
        $resourceConnection->method('getConnection')->willReturn($this->connection);
        $this->query = $this->createMock(ReviewSummaryQuery::class);

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStores')->willReturn([
            $this->createStore(1, 'default'),
            $this->createStore(2, 'fr'),
        ]);
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('isSetFlag')->willReturnCallback(
            fn (string $path, string $scope, int $storeId): bool => $path === 'catalog/review/active'
                && $scope === 'store'
                && ($this->reviewsActive[$storeId] ?? false)
        );

        $this->source = new ReviewSummarySource(
            $resourceConnection,
            $this->query,
            new ReviewAttributeContract(),
            $storeManager,
            $scopeConfig
        );
    }

    public function testIsEnabledWhenReviewsAreActiveInAnyStoreView(): void
    {
        $this->reviewsActive = [1 => false, 2 => true];

        $this->assertTrue($this->source->isEnabled());
    }

    public function testIsDisabledWhenReviewsAreInactiveInAllStoreViews(): void
    {
        $this->reviewsActive = [1 => false, 2 => false];

        $this->assertFalse($this->source->isEnabled());
    }

    public function testGetSummariesOnlyQueriesEnabledStoreViews(): void
    {
        $this->reviewsActive = [1 => true, 2 => false];
        $select = $this->createMock(Select::class);
        $this->query->expects($this->once())
            ->method('getSummariesSelect')
            ->with([10, 11], ['default'])
            ->willReturn($select);
        $this->connection->method('fetchAll')->with($select)->willReturn([
            ['productId' => '10', 'storeViewCode' => 'default', 'reviewCount' => '2', 'ratingSummary' => '90'],
            ['productId' => '11', 'storeViewCode' => 'default', 'reviewCount' => '0', 'ratingSummary' => '0'],
        ]);

        $this->assertSame(
            [
                '10-default' => ['reviewCount' => 2, 'ratingSummary' => 90.0],
                '11-default' => ['reviewCount' => 0, 'ratingSummary' => 0.0],
            ],
            $this->source->getSummaries([10, 11], ['default', 'fr'])
        );
    }

    public function testGetSummariesReturnsNothingWhenRequestedStoreViewsAreDisabled(): void
    {
        $this->reviewsActive = [1 => true, 2 => false];
        $this->query->expects($this->never())->method('getSummariesSelect');

        $this->assertSame([], $this->source->getSummaries([10], ['fr']));
    }

    public function testGetSummariesPropagatesReadErrors(): void
    {
        $exception = new \RuntimeException('db error');
        $this->reviewsActive = [1 => true];
        $this->query->method('getSummariesSelect')->willReturn($this->createMock(Select::class));
        $this->connection->method('fetchAll')->willThrowException($exception);

        $this->expectExceptionObject($exception);

        $this->source->getSummaries([10], ['default']);
    }

    /**
     * @param int $id
     * @param string $code
     * @return StoreInterface&MockObject
     */
    private function createStore(int $id, string $code): StoreInterface&MockObject
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn($id);
        $store->method('getCode')->willReturn($code);

        return $store;
    }
}
