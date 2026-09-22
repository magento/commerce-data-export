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

namespace AdobeCommerce\IndexerStatusManager\Test\Integration\Plugin;

use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Indexer\StateInterface;
use Magento\Indexer\Model\Indexer\State;
use AdobeCommerce\IndexerStatusManager\Model\Config;
use AdobeCommerce\IndexerStatusManager\Plugin\DisableIndexerReindex;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Fixture\Config as ConfigFixture;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration coverage for DisableIndexerReindex.
 *
 * Wired on IndexerInterface, so it gates reindexAll (full reindex) and reindexRow/reindexList
 * (save-time). A disabled indexer must skip all three, regardless of caller.
 *
 * Coverage: end-to-end via a real indexer instance (reindexAll), plus a direct spy-$proceed check for all
 * three methods (reindexRow/reindexList effects aren't otherwise observable without fixtures).
 *
 * Indexer status is read from the state row, not IndexerRegistry: a disabled indexer is hidden from the
 * registry by RemoveIndexersFromConfig, so a registry lookup would throw for the states under test. For the
 * same reason the end-to-end disable case grabs the indexer instance while it is still enabled and only then
 * flips the flag in the live config, so the plugin engages on a reference the test already holds.
 *
 * DbIsolation is off: reindexAll issues DDL, which the transaction adapter rejects; tearDown restores
 * the indexer status.
 */
#[
    AppIsolation(true),
    DbIsolation(false)
]
class DisableIndexerReindexTest extends TestCase
{
    private const INDEXER_CODE = 'catalog_category_product';

    /**
     * @var IndexerRegistry
     */
    private IndexerRegistry $indexerRegistry;

    /**
     * @var MutableScopeConfigInterface
     */
    private MutableScopeConfigInterface $mutableConfig;

    /**
     * @var string
     */
    private string $originalStatus = '';

    protected function setUp(): void
    {
        parent::setUp();
        $objectManager = Bootstrap::getObjectManager();
        // Empty allowlist by default; make the indexer under test manageable.
        $objectManager->configure([
            Config::class => [
                'arguments' => [
                    'manageableIndexers' => ['catalog_category_product', 'catalog_product_category'],
                ],
            ],
        ]);
        $this->indexerRegistry = $objectManager->get(IndexerRegistry::class);
        $this->mutableConfig = $objectManager->get(MutableScopeConfigInterface::class);
        $this->originalStatus = $this->indexerStatus();
    }

    protected function tearDown(): void
    {
        // Status changes are persisted (DbIsolation is off); restore what we invalidated/reindexed.
        $state = Bootstrap::getObjectManager()->create(State::class);
        $state->loadByIndexer(self::INDEXER_CODE);
        $state->setIndexerId(self::INDEXER_CODE);
        $state->setStatus($this->originalStatus);
        $state->save();
        parent::tearDown();
    }

    /**
     * A disabled indexer skips reindexAll(): an invalidated indexer stays invalid.
     */
    public function testDisabledIndexerSkipsFullReindex(): void
    {
        // Grab the indexer while it is still enabled/visible, then disable it in the live config so the
        // plugin engages on the reference we already hold (a disabled indexer is not resolvable anew).
        $indexer = $this->indexerRegistry->get(self::INDEXER_CODE);
        $indexer->invalidate();
        $this->assertSame(
            StateInterface::STATUS_INVALID,
            $this->indexerStatus(),
            'Precondition: the indexer must start invalid.'
        );

        $this->mutableConfig->setValue('ac_disabled_indexers/' . self::INDEXER_CODE, 1);

        $indexer->reindexAll();

        $this->assertSame(
            StateInterface::STATUS_INVALID,
            $this->indexerStatus(),
            'A disabled indexer must not run reindexAll(); it must stay invalid.'
        );
    }

    /**
     * Control: with the flag cleared, the same indexer reindexes normally.
     */
    #[ConfigFixture('ac_disabled_indexers/catalog_category_product', 0)]
    public function testEnabledIndexerRunsFullReindex(): void
    {
        $indexer = $this->indexerRegistry->get(self::INDEXER_CODE);
        $indexer->invalidate();
        $this->assertSame(
            StateInterface::STATUS_INVALID,
            $this->indexerStatus(),
            'Precondition: the indexer must start invalid.'
        );

        $indexer->reindexAll();

        $this->assertSame(
            StateInterface::STATUS_VALID,
            $this->indexerStatus(),
            'An enabled indexer must reindex normally and become valid.'
        );
    }

    /**
     * An unmanaged indexer (not allowlisted, no disabled flag) reindexes normally, untouched.
     */
    public function testUnmanagedIndexerReindexesNormally(): void
    {
        $code = 'catalogrule_rule';
        $indexer = $this->indexerRegistry->get($code);
        $original = $this->indexerStatus($code);

        try {
            $indexer->invalidate();
            $this->assertSame(
                StateInterface::STATUS_INVALID,
                $this->indexerStatus($code),
                'Precondition: the indexer must start invalid.'
            );

            $indexer->reindexAll();

            $this->assertSame(
                StateInterface::STATUS_VALID,
                $this->indexerStatus($code),
                'An unmanaged indexer must reindex normally and become valid.'
            );
        } finally {
            $state = Bootstrap::getObjectManager()->create(State::class);
            $state->loadByIndexer($code);
            $state->setIndexerId($code);
            $state->setStatus($original);
            $state->save();
        }
    }

    /**
     * When disabled, none of reindexAll/reindexRow/reindexList reach the wrapped implementation.
     */
    #[ConfigFixture('ac_disabled_indexers/catalog_category_product', 1)]
    public function testDisabledIndexerSkipsEveryReindexMethod(): void
    {
        $calls = $this->countProceedCalls();

        $this->assertSame(
            0,
            $calls,
            'A disabled indexer must skip reindexAll(), reindexRow() and reindexList().'
        );
    }

    /**
     * When enabled, every reindex method is allowed through to the wrapped implementation.
     */
    #[ConfigFixture('ac_disabled_indexers/catalog_category_product', 0)]
    public function testEnabledIndexerInvokesEveryReindexMethod(): void
    {
        $calls = $this->countProceedCalls();

        $this->assertSame(
            3,
            $calls,
            'An enabled indexer must let reindexAll(), reindexRow() and reindexList() proceed.'
        );
    }

    /**
     * Reads an indexer's current status straight from its state row (bypasses IndexerRegistry).
     *
     * @param string $code
     * @return string
     */
    private function indexerStatus(string $code = self::INDEXER_CODE): string
    {
        $state = Bootstrap::getObjectManager()->create(State::class);
        $state->loadByIndexer($code);

        return (string)$state->getStatus();
    }

    /**
     * Drives the plugin's three around-methods with a spy $proceed and a stub subject reporting the
     * indexer id under test, returning how many of them were allowed through.
     *
     * @return int
     */
    private function countProceedCalls(): int
    {
        $plugin = Bootstrap::getObjectManager()->create(DisableIndexerReindex::class);

        $subject = $this->createStub(IndexerInterface::class);
        $subject->method('getId')->willReturn(self::INDEXER_CODE);

        $calls = 0;
        $proceed = function () use (&$calls): void {
            $calls++;
        };

        $plugin->aroundReindexAll($subject, $proceed);
        $plugin->aroundReindexRow($subject, $proceed, 1);
        $plugin->aroundReindexList($subject, $proceed, [1]);

        return $calls;
    }
}
