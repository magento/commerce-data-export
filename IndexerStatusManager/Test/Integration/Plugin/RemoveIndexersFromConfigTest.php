<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\IndexerStatusManager\Test\Integration\Plugin;

use Magento\Indexer\Model\Config as IndexerConfig;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Fixture\Config as ConfigFixture;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration coverage for RemoveIndexersFromConfig.
 *
 * A disabled allowlisted indexer must drop out of the indexer configuration list, while a
 * non-allowlisted indexer must remain even if a disabled flag has been set for it.
 */
#[AppIsolation(true)]
class RemoveIndexersFromConfigTest extends TestCase
{
    /**
     * @var IndexerConfig|null
     */
    private ?IndexerConfig $indexerConfig = null;

    protected function setUp(): void
    {
        parent::setUp();
        // The generic module ships an empty allowlist; provide a test allowlist so the plugin (which
        // reads Config) recognizes the catalog indexers used below.
        Bootstrap::getObjectManager()->configure([
            \AdobeCommerce\IndexerStatusManager\Model\Config::class => [
                'arguments' => [
                    'manageableIndexers' => ['catalog_category_product', 'catalog_product_category'],
                ],
            ],
        ]);
        $this->indexerConfig = Bootstrap::getObjectManager()->create(IndexerConfig::class);
    }

    /**
     * A disabled allowlisted indexer is removed from the indexer configuration list.
     */
    #[ConfigFixture('ac_disabled_indexers/catalog_category_product', 1)]
    public function testDisabledAllowlistedIndexerRemovedFromList(): void
    {
        $this->assertArrayNotHasKey('catalog_category_product', $this->indexerConfig->getIndexers());
    }

    /**
     * With the flag cleared, the allowlisted indexer is listed again.
     */
    #[ConfigFixture('ac_disabled_indexers/catalog_category_product', 0)]
    public function testAllowlistedIndexerPresentWhenEnabled(): void
    {
        $this->assertArrayHasKey('catalog_category_product', $this->indexerConfig->getIndexers());
    }

    /**
     * The guardrail at list level: a flag set for a non-allowlisted critical indexer does not remove it.
     */
    #[ConfigFixture('ac_disabled_indexers/catalog_product_price', 1)]
    public function testNonAllowlistedIndexerRemainsDespiteFlag(): void
    {
        $this->assertArrayHasKey('catalog_product_price', $this->indexerConfig->getIndexers());
    }

    /**
     * A disabled allowlisted indexer is also hidden from the single-ID lookup (getIndexer()).
     */
    #[ConfigFixture('ac_disabled_indexers/catalog_category_product', 1)]
    public function testDisabledAllowlistedIndexerHiddenFromSingleLookup(): void
    {
        $this->assertSame([], $this->indexerConfig->getIndexer('catalog_category_product'));
    }

    /**
     * With the flag cleared, the single-ID lookup returns the indexer again.
     */
    #[ConfigFixture('ac_disabled_indexers/catalog_category_product', 0)]
    public function testAllowlistedIndexerPresentInSingleLookupWhenEnabled(): void
    {
        $this->assertNotSame([], $this->indexerConfig->getIndexer('catalog_category_product'));
    }

    /**
     * The guardrail at single-ID lookup level: a non-allowlisted critical indexer stays visible.
     */
    #[ConfigFixture('ac_disabled_indexers/catalog_product_price', 1)]
    public function testNonAllowlistedIndexerSingleLookupUnaffectedByFlag(): void
    {
        $this->assertNotSame([], $this->indexerConfig->getIndexer('catalog_product_price'));
    }
}
