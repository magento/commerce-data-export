<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Unit\Category;

use Magento\DataExporter\Model\Indexer\Config;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use PHPUnit\Framework\TestCase;

class CategoryFeedIdentityTest extends TestCase
{
    public function testCategoryFeedIdentifiersUseCategoryIdAndStoreViewCode(): void
    {
        $config = $this->createMock(Config::class);
        $metadata = new FeedIndexMetadata(
            'categories',
            'catalog_category_entity',
            'entity_id',
            'categoryId',
            'cde_categories_feed',
            FeedIndexMetadata::FEED_TABLE_FIELD_SOURCE_ENTITY_ID,
            [],
            null,
            0,
            'updated_at',
            true,
            [],
            [
                'categoryId' => 'categoryId',
                'storeViewCode' => 'storeViewCode',
            ],
            [],
            [],
            false,
            false,
            true,
            null,
            $config
        );

        $this->assertSame(
            ['categoryId' => 'categoryId', 'storeViewCode' => 'storeViewCode'],
            $metadata->getFeedItemIdentifiers()
        );
    }
}
