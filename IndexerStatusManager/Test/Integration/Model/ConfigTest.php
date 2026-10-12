<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\IndexerStatusManager\Test\Integration\Model;

use AdobeCommerce\IndexerStatusManager\Model\Config;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Fixture\Config as ConfigFixture;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration coverage for the config-backed allowlist and disabled-flag resolution.
 *
 * The central guarantee under test is that the disabled flag is honored only for allowlisted indexers,
 * so a critical indexer (price, stock, ...) can never be switched off through this mechanism.
 */
#[AppIsolation(true)]
class ConfigTest extends TestCase
{
    private const ALLOWLISTED_INDEXER = 'catalog_category_product';
    private const CRITICAL_INDEXER = 'catalog_product_price';

    /**
     * @var Config|null
     */
    private ?Config $config = null;

    protected function setUp(): void
    {
        parent::setUp();
        // The generic module ships an empty allowlist; a consumer contributes real indexer codes.
        // Provide a test allowlist so these tests are self-contained.
        Bootstrap::getObjectManager()->configure([
            Config::class => [
                'arguments' => [
                    'manageableIndexers' => ['catalog_category_product', 'catalog_product_category'],
                    // Shape matches the self-named nested arrays di.xml parses into (indexerCode => tables).
                    'indexTables' => [
                        'catalog_category_product' => [
                            'catalog_category_product_index' => 'catalog_category_product_index',
                        ],
                        'catalog_product_category' => [
                            'catalog_category_product_index' => 'catalog_category_product_index',
                        ],
                    ],
                ],
            ],
        ]);
        $this->config = Bootstrap::getObjectManager()->get(Config::class);
    }

    /**
     * The allowlist injected via di.xml exposes the shipped catalog indexers.
     */
    public function testAllowlistExposesManageableIndexers(): void
    {
        $manageable = $this->config->getManageableIndexers();
        $this->assertContains('catalog_category_product', $manageable);
        $this->assertContains('catalog_product_category', $manageable);
    }

    /**
     * isManageable() reflects the allowlist and rejects everything else.
     */
    public function testIsManageableReflectsAllowlist(): void
    {
        $this->assertTrue($this->config->isManageable(self::ALLOWLISTED_INDEXER));
        $this->assertFalse($this->config->isManageable(self::CRITICAL_INDEXER));
        $this->assertFalse($this->config->isManageable('made_up_indexer'));
    }

    /**
     * An allowlisted indexer with the flag set reports disabled.
     */
    #[ConfigFixture('ac_disabled_indexers/catalog_category_product', 1)]
    public function testAllowlistedIndexerDisabledWhenFlagSet(): void
    {
        $this->assertTrue($this->config->isDisabled(self::ALLOWLISTED_INDEXER));
    }

    /**
     * An allowlisted indexer with the flag cleared is not disabled.
     */
    #[ConfigFixture('ac_disabled_indexers/catalog_category_product', 0)]
    public function testAllowlistedIndexerNotDisabledWhenFlagCleared(): void
    {
        $this->assertFalse($this->config->isDisabled(self::ALLOWLISTED_INDEXER));
    }

    /**
     * The guardrail: a flag set for a non-allowlisted (critical) indexer is ignored on read, so it can
     * never be disabled by a stray CLI call, config:set, or hand-edited env.php.
     */
    #[ConfigFixture('ac_disabled_indexers/catalog_product_price', 1)]
    public function testCriticalIndexerNeverDisabledEvenWithFlagSet(): void
    {
        $this->assertFalse(
            $this->config->isDisabled(self::CRITICAL_INDEXER),
            'A non-allowlisted indexer must not be reported disabled even when its config flag is set.'
        );
    }

    /**
     * The per-indexer index-table map supplied via di.xml is resolved: getIndexTables() returns the
     * configured entry, its values are the table names truncateIndexTables() iterates, and an indexer with
     * no mapping returns an empty list.
     */
    public function testIndexTablesMapIsResolved(): void
    {
        $this->assertSame(
            ['catalog_category_product_index' => 'catalog_category_product_index'],
            $this->config->getIndexTables('catalog_category_product')
        );
        $this->assertSame(
            ['catalog_category_product_index'],
            array_values($this->config->getIndexTables('catalog_product_category')),
            'The map values are the table names truncateIndexTables() iterates over.'
        );
        $this->assertSame(
            [],
            $this->config->getIndexTables('catalog_product_price'),
            'An indexer with no configured index table returns an empty list.'
        );
        $this->assertSame([], $this->config->getIndexTables('made_up_indexer'));
    }
}
