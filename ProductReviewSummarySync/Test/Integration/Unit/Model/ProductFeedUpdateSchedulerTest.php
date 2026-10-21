<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\ProductReviewSummarySync\Test\Integration\Unit\Model;

use AdobeCommerce\ProductReviewSummarySync\Model\ProductFeedUpdateScheduler;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Mview\View\ChangelogInterface;
use Magento\Framework\Mview\ViewInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductFeedUpdateSchedulerTest extends TestCase
{
    /**
     * @var IndexerInterface&MockObject
     */
    private IndexerInterface $indexer;

    /**
     * @var AdapterInterface&MockObject
     */
    private AdapterInterface $connection;

    /**
     * @var CommerceDataExportLoggerInterface&MockObject
     */
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @var ProductFeedUpdateScheduler
     */
    private ProductFeedUpdateScheduler $scheduler;

    protected function setUp(): void
    {
        $changelog = $this->createMock(ChangelogInterface::class);
        $changelog->method('getName')->willReturn('cde_products_feed_cl');
        $changelog->method('getColumnName')->willReturn('entity_id');
        $view = $this->createMock(ViewInterface::class);
        $view->method('getChangelog')->willReturn($changelog);
        $this->indexer = $this->createMock(IndexerInterface::class);
        $this->indexer->method('getView')->willReturn($view);
        $indexerRegistry = $this->createMock(IndexerRegistry::class);
        $indexerRegistry->method('get')->with('catalog_data_exporter_products')->willReturn($this->indexer);

        $this->connection = $this->createMock(AdapterInterface::class);
        $resourceConnection = $this->createMock(ResourceConnection::class);
        $resourceConnection->method('getConnection')->willReturn($this->connection);
        $resourceConnection->method('getTableName')->willReturnArgument(0);
        $this->logger = $this->createMock(CommerceDataExportLoggerInterface::class);

        $this->scheduler = new ProductFeedUpdateScheduler($indexerRegistry, $resourceConnection, $this->logger);
    }

    public function testScheduleFromSelectInsertsIntoChangelog(): void
    {
        $this->indexer->method('isScheduled')->willReturn(true);
        $select = $this->createMock(Select::class);
        $select->expects($this->once())
            ->method('insertFromSelect')
            ->with('cde_products_feed_cl', ['entity_id'], false)
            ->willReturn('INSERT SQL');
        $this->connection->expects($this->once())->method('query')->with('INSERT SQL');

        $this->scheduler->scheduleFromSelect($select);
    }

    public function testScheduleFromSelectLogsError(): void
    {
        $this->indexer->method('isScheduled')->willReturn(true);
        $select = $this->createMock(Select::class);
        $select->method('insertFromSelect')->willReturn('INSERT SQL');
        $this->connection->method('query')->willThrowException(new \RuntimeException('db error'));
        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'CDE03-29 Product sync scheduling error on review summary change',
                $this->callback(static fn (array $context) => $context['error'] === 'db error')
            );

        $this->scheduler->scheduleFromSelect($select);
    }

    public function testScheduleFromSelectSkipsWhenIndexerIsNotScheduled(): void
    {
        $this->indexer->method('isScheduled')->willReturn(false);
        $select = $this->createMock(Select::class);
        $select->expects($this->never())->method('insertFromSelect');
        $this->connection->expects($this->never())->method('query');

        $this->scheduler->scheduleFromSelect($select);
    }
}
