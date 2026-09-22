<?php
/**
 * Copyright 2026 Adobe
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
 */
declare(strict_types=1);

namespace AdobeCommerce\IndexerStatusManager\Test\Integration\Console\Command;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Console\Cli;
use Magento\Framework\Indexer\IndexerRegistry;
use AdobeCommerce\IndexerStatusManager\Console\Command\IndexerDisable;
use AdobeCommerce\IndexerStatusManager\Model\Config;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Integration coverage for the indexer:disable command guardrail.
 *
 * Covers the refusal path (non-allowlisted indexer, returns before doing anything) and the accepted path
 * (allowlisted indexer, which writes the disabled flag and unsubscribes the mview). DbIsolation is
 * disabled because the accepted path issues DDL (drop triggers/changelog), which cannot run inside a
 * transaction; tearDown restores the config flag and the indexer's subscription state.
 */
#[
    AppIsolation(true),
    DbIsolation(false)
]
class IndexerDisableCommandTest extends TestCase
{
    private const INDEXER_CODE = 'catalog_category_product';
    private const FLAG_PATH = 'ac_disabled_indexers/' . self::INDEXER_CODE;

    /**
     * @var IndexerRegistry
     */
    private IndexerRegistry $indexerRegistry;

    /**
     * @var bool
     */
    private bool $wasScheduled = false;

    protected function setUp(): void
    {
        parent::setUp();
        $objectManager = Bootstrap::getObjectManager();
        // The generic module ships an empty allowlist; provide a test allowlist so the command treats the
        // catalog indexers as manageable.
        $objectManager->configure([
            Config::class => [
                'arguments' => [
                    'manageableIndexers' => ['catalog_category_product', 'catalog_product_category'],
                ],
            ],
        ]);
        $this->indexerRegistry = $objectManager->get(IndexerRegistry::class);
        $this->wasScheduled = $this->indexerRegistry->get(self::INDEXER_CODE)->isScheduled();
    }

    protected function tearDown(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        // Undo the accepted path's side effects (config flag + unsubscribe); DDL is not rolled back.
        $objectManager->get(WriterInterface::class)->delete(self::FLAG_PATH);
        $this->indexerRegistry->get(self::INDEXER_CODE)->setScheduled($this->wasScheduled);
        parent::tearDown();
    }

    /**
     * The command refuses to disable an indexer that is not on the allowlist and returns a failure code.
     */
    public function testRefusesNonAllowlistedIndexer(): void
    {
        $command = Bootstrap::getObjectManager()->create(IndexerDisable::class);
        $tester = new CommandTester($command);

        $tester->execute(['indexer' => 'catalog_product_price']);

        $this->assertSame(Cli::RETURN_FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('cannot be disabled', $tester->getDisplay());
        $this->assertStringContainsString('catalog_category_product', $tester->getDisplay());
    }

    /**
     * The command accepts an allowlisted indexer (passes the guard and reaches the disable step).
     */
    public function testAcceptsAllowlistedIndexer(): void
    {
        $command = Bootstrap::getObjectManager()->create(IndexerDisable::class);
        $tester = new CommandTester($command);

        $tester->execute(['indexer' => self::INDEXER_CODE]);

        $this->assertSame(Cli::RETURN_SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('has been disabled', $tester->getDisplay());
    }
}
