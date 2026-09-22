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

namespace AdobeCommerce\IndexerStatusManager\Test\Integration\Model;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Indexer\StateInterface;
use Magento\Framework\Mview\ViewInterface;
use Magento\Framework\Mview\ViewInterfaceFactory;
use Magento\Indexer\Model\Config\Data as IndexerConfigData;
use Magento\Indexer\Model\Indexer\State;
use AdobeCommerce\IndexerStatusManager\Model\Config;
use AdobeCommerce\IndexerStatusManager\Model\Indexer\StatusManager;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Fixture\Config as ConfigFixture;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration coverage for the mview-trigger lifecycle of enabling/disabling an indexer.
 *
 * Disabling unsubscribes the indexer's view (drops its changelog table + every DB trigger that writes to
 * it); enabling re-subscribes it (recreates them). Assertions go down to the database schema, not just the
 * indexer mode, and read the mview View directly by its view_id rather than through IndexerRegistry -- the
 * RemoveIndexersFromConfig plugin hides a disabled indexer from IndexerRegistry, so a registry lookup would
 * throw for exactly the states under test. Reading the View keeps the assertions independent of that plugin
 * and of when config fixtures are applied relative to setUp().
 *
 * DbIsolation must stay disabled: subscribe()/unsubscribe() issue DDL (create/drop triggers + changelog
 * table), and the DbIsolation transaction adapter rejects DDL. The test is self-cleaning instead - tearDown
 * restores the view subscription state and clears the disabled flag it may have written.
 */
#[
    AppIsolation(true),
    DbIsolation(false)
]
class IndexerTriggerLifecycleTest extends TestCase
{
    private const INDEXER_CODE = 'catalog_category_product';
    private const CHANGELOG_TABLE = 'catalog_category_product_cl';

    /**
     * @var StatusManager
     */
    private StatusManager $service;

    /**
     * @var AdapterInterface
     */
    private AdapterInterface $connection;

    /**
     * @var ViewInterfaceFactory
     */
    private ViewInterfaceFactory $viewFactory;

    /**
     * @var WriterInterface
     */
    private WriterInterface $configWriter;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var string
     */
    private string $viewId = '';

    /**
     * @var bool
     */
    private bool $wasEnabled = false;

    protected function setUp(): void
    {
        parent::setUp();
        $objectManager = Bootstrap::getObjectManager();
        // The generic module ships an empty allowlist; provide a test allowlist so the service treats
        // the catalog indexer under test as manageable.
        $objectManager->configure([
            Config::class => [
                'arguments' => [
                    'manageableIndexers' => ['catalog_category_product', 'catalog_product_category'],
                ],
            ],
        ]);
        $this->service = $objectManager->get(StatusManager::class);
        $this->connection = $objectManager->get(ResourceConnection::class)->getConnection();
        $this->viewFactory = $objectManager->get(ViewInterfaceFactory::class);
        $this->configWriter = $objectManager->get(WriterInterface::class);
        $this->config = $objectManager->get(Config::class);
        $indexerConfig = $objectManager->get(IndexerConfigData::class)->get(self::INDEXER_CODE);
        $this->viewId = (string)($indexerConfig['view_id'] ?? '');
        $this->wasEnabled = $this->view()->isEnabled();
    }

    protected function tearDown(): void
    {
        // Config writes are persisted (DbIsolation is off); clear the disabled flag the test may have set.
        $this->configWriter->delete($this->config->getConfigPath(self::INDEXER_CODE));
        // Restore the original subscription state (DDL is not rolled back by the framework).
        $view = $this->view();
        if ($this->wasEnabled) {
            $view->subscribe();
        } else {
            $view->unsubscribe();
        }
        parent::tearDown();
    }

    /**
     * Disabling a subscribed indexer drops its changelog table and every DB trigger that writes to it.
     */
    public function testDisableRemovesMviewTriggersAndChangelog(): void
    {
        $this->view()->subscribe();
        $this->assertSubscribed('Precondition');

        $this->service->disable(self::INDEXER_CODE);

        $this->assertUnsubscribed('After disable');
        $this->assertSame(
            StateInterface::STATUS_INVALID,
            $this->indexerStatus(),
            'Disabling must invalidate the now-unmaintained index so it rebuilds whenever re-enabled.'
        );
    }

    /**
     * Enabling a disabled indexer restores its triggers and invalidates it; disabling it removes them again.
     *
     * Covers the full lifecycle: disabled -> enable (triggers back + invalidated) -> disable (triggers gone).
     */
    public function testEnableRestoresTriggersThenDisableRemovesThem(): void
    {
        // Start from a known disabled/unsubscribed state.
        $this->view()->subscribe();
        $this->service->disable(self::INDEXER_CODE);
        $this->assertUnsubscribed('Baseline (disabled)');

        // Enable -> triggers restored and the stale index invalidated for rebuild.
        $this->service->enable(self::INDEXER_CODE);
        $this->assertSubscribed('After enable');
        $this->assertSame(
            StateInterface::STATUS_INVALID,
            $this->indexerStatus(),
            'After enable: the indexer must be invalidated so its stale index is rebuilt on next reindex.'
        );

        // Disable again -> triggers removed.
        $this->service->disable(self::INDEXER_CODE);
        $this->assertUnsubscribed('After disable again');
    }

    /**
     * The setup Recurring path removes triggers for a config-disabled indexer.
     *
     * removeTriggersForDisabledIndexers() is exactly what IndexerStatusManager\Setup\Recurring calls on
     * every setup:upgrade (after core's Recurring re-subscribes indexers), so this covers the shipped
     * config default being applied on deploy for both fresh and existing installs. With the flag set the
     * indexer is hidden from IndexerRegistry, so this also proves the service reaches it anyway (it reads
     * the view directly). Assertions read the mview View, not IndexerRegistry, for the same reason.
     */
    #[ConfigFixture('ac_disabled_indexers/catalog_category_product', 1)]
    public function testRecurringCleanupRemovesTriggersForConfigDisabledIndexer(): void
    {
        // Simulate core (re)subscribing the indexer, as Magento\Indexer\Setup\Recurring does on upgrade.
        $this->view()->subscribe();
        $this->assertSubscribed('Precondition (core re-subscribed)');

        // Run the method the setup Recurring invokes for the config-disabled indexer.
        $this->service->removeTriggersForDisabledIndexers();

        // Assert: the config-disabled indexer is unsubscribed again.
        $this->assertUnsubscribed('After recurring cleanup');
    }

    /**
     * The setup Recurring path also restores triggers once the disabled flag is gone (e.g. removed
     * directly from env.php rather than via our enable() CLI command).
     *
     * Without this direction, an indexer disabled that way would stay unsubscribed forever, since core's
     * own Recurring skips anything already in disabled mode.
     */
    public function testRecurringRestoresTriggersOnceNoLongerConfigDisabled(): void
    {
        // Simulate the indexer having been left unsubscribed by a prior disabled config state.
        $this->view()->unsubscribe();
        $this->assertUnsubscribed('Precondition (left disabled)');

        // No ConfigFixture here: the flag is absent/false, same as removing it from env.php.
        $this->service->restoreTriggersForEnabledIndexers();

        $this->assertSubscribed('After recurring sync with no disabled flag');
        $this->assertSame(
            StateInterface::STATUS_INVALID,
            $this->indexerStatus(),
            'Restoring triggers must invalidate the indexer so its stale index is rebuilt on next reindex.'
        );
    }

    /**
     * Loads a fresh mview View for the indexer under test (bypasses IndexerRegistry).
     *
     * @return ViewInterface
     */
    private function view(): ViewInterface
    {
        $view = $this->viewFactory->create();
        $view->load($this->viewId);

        return $view;
    }

    /**
     * Reads the indexer's current status straight from its state row (bypasses IndexerRegistry).
     *
     * @return string
     */
    private function indexerStatus(): string
    {
        $state = Bootstrap::getObjectManager()->create(State::class);
        $state->loadByIndexer(self::INDEXER_CODE);

        return (string)$state->getStatus();
    }

    /**
     * Asserts the indexer is subscribed: mview enabled, changelog table present, triggers writing to it.
     *
     * @param string $stage
     * @return void
     */
    private function assertSubscribed(string $stage): void
    {
        $this->assertTrue(
            $this->view()->isEnabled(),
            "$stage: the indexer's view must be subscribed."
        );
        $this->assertTrue(
            $this->changelogTableExists(),
            "$stage: the mview changelog table must exist."
        );
        $this->assertGreaterThan(
            0,
            $this->countTriggersWritingToChangelog(),
            "$stage: DB triggers writing to the changelog must exist."
        );
    }

    /**
     * Asserts the indexer is unsubscribed: mview not enabled, changelog table gone, no triggers writing.
     *
     * @param string $stage
     * @return void
     */
    private function assertUnsubscribed(string $stage): void
    {
        $this->assertFalse(
            $this->view()->isEnabled(),
            "$stage: the indexer's view must be unsubscribed."
        );
        $this->assertFalse(
            $this->changelogTableExists(),
            "$stage: the mview changelog table must be dropped."
        );
        $this->assertSame(
            0,
            $this->countTriggersWritingToChangelog(),
            "$stage: no DB trigger may write to the changelog."
        );
    }

    /**
     * Whether the mview changelog table currently exists.
     *
     * @return bool
     */
    private function changelogTableExists(): bool
    {
        return $this->connection->isTableExists($this->connection->getTableName(self::CHANGELOG_TABLE));
    }

    /**
     * Counts DB triggers in the current schema whose body writes to this indexer's changelog table.
     *
     * Robust to triggers shared with other indexers: unsubscribing rebuilds a shared trigger without this
     * view's INSERT statement, so a reference to this changelog table disappears even if the trigger object
     * lives on for another view.
     *
     * @return int
     */
    private function countTriggersWritingToChangelog(): int
    {
        $changelogTable = $this->connection->getTableName(self::CHANGELOG_TABLE);

        return (int)$this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TRIGGERS'
            . ' WHERE TRIGGER_SCHEMA = DATABASE() AND ACTION_STATEMENT LIKE ?',
            ['%' . $changelogTable . '%']
        );
    }
}
