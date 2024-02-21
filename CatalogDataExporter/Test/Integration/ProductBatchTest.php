<?php
/*************************************************************************
 *
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ***********************************************************************
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Integration;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\DataExporter\Model\Batch\FeedSource\Generator;
use Magento\DataExporter\Model\FeedInterface;
use Magento\DataExporter\Model\FeedPool;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test feed source batch generation for products.
 */
class ProductBatchTest extends TestCase
{
    /**
     * @var ?Generator
     */
    private ?Generator $productFeedSourceGenerator;
    public ?FeedInterface $productFeed;

    /**
     * Integration test setup
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->productFeed = Bootstrap::getObjectManager()->get(FeedPool::class)->getFeed('products');
        $this->productFeedSourceGenerator = Bootstrap::getObjectManager()->create(Generator::class);
    }

    #[
        Config('commerce_data_export/feeds/products/batch_size', 5),
        DbIsolation(false),
        DataFixture(ProductFixture::class, count: 10),
    ]
    public function testProductFeedSourceGenerator() : void
    {
        $batchIterator = $this->productFeedSourceGenerator->generate($this->productFeed->getFeedMetadata());
        self::assertEquals(2, $batchIterator->count(), 'Batch count is wrong');
        foreach ($batchIterator as $ids) {
            self::assertCount(5, $ids, 'The number of products in batch is wrong');
        }
    }
}
