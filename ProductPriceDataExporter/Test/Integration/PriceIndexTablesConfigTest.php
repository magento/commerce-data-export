<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\ProductPriceDataExporter\Test\Integration;

use AdobeCommerce\IndexerStatusManager\Model\Config;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Verifies this module's di.xml wiring for IndexerStatusManager: catalog_product_price is allowlisted and
 * its index tables are supplied, so disabling it truncates the price index read table and its replica.
 *
 * Reads Config straight from the object manager (no test override), so it exercises the real merged
 * di.xml contribution rather than a hand-built array.
 */
#[AppIsolation(true)]
class PriceIndexTablesConfigTest extends TestCase
{
    private const PRICE_INDEXER = 'catalog_product_price';

    /**
     * catalog_product_price is manageable and its read table + replica are declared for truncation.
     */
    public function testPriceIndexerIsManageableWithItsIndexTables(): void
    {
        $config = Bootstrap::getObjectManager()->get(Config::class);

        $this->assertTrue(
            $config->isManageable(self::PRICE_INDEXER),
            'ProductPriceDataExporter must allowlist catalog_product_price for disabling.'
        );
        $this->assertSame(
            ['catalog_product_index_price', 'catalog_product_index_price_replica'],
            array_values($config->getIndexTables(self::PRICE_INDEXER)),
            'Disabling catalog_product_price must truncate its read table and replica.'
        );
    }
}
