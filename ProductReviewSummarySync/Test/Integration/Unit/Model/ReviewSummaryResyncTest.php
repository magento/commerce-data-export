<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\ProductReviewSummarySync\Test\Integration\Unit\Model;

use AdobeCommerce\ProductReviewSummarySync\Model\ProductFeedUpdateScheduler;
use AdobeCommerce\ProductReviewSummarySync\Model\Query\ReviewSummaryQuery;
use AdobeCommerce\ProductReviewSummarySync\Model\ReviewSummaryResync;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\TestCase;

class ReviewSummaryResyncTest extends TestCase
{
    public function testReconcileSchedulesAllReviewedProducts(): void
    {
        $select = $this->createMock(Select::class);
        $query = $this->createMock(ReviewSummaryQuery::class);
        $query->method('getReviewedProductIdsSelect')->willReturn($select);
        $scheduler = $this->createMock(ProductFeedUpdateScheduler::class);
        $scheduler->expects($this->once())->method('scheduleFromSelect')->with($select);

        (new ReviewSummaryResync($query, $scheduler))->reconcile();
    }
}
