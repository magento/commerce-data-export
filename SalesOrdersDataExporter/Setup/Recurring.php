<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\SalesOrdersDataExporter\Setup;

use InvalidArgumentException;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerInterfaceFactory;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Psr\Log\LoggerInterface;

/**
 * Ensures orders' export indexer is in mode 'Update by Schedule' on each upgrade.
 */
class Recurring implements InstallSchemaInterface
{
    private const ORDERS_INDEXER_NAME = 'sales_order_data_exporter_v2';

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    /**
     * @var IndexerInterfaceFactory
     */
    private IndexerInterfaceFactory $indexerInterfaceFactory;

    /**
     * @param LoggerInterface $logger
     * @param IndexerInterfaceFactory $indexerInterfaceFactory
     */
    public function __construct(LoggerInterface $logger, IndexerInterfaceFactory $indexerInterfaceFactory)
    {
        $this->logger = $logger;
        $this->indexerInterfaceFactory = $indexerInterfaceFactory;
    }

    /**
     * If orders indexer is found, will force mode to be On Schedule
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context): void
    {
        $setup->startSetup();

        $ordersIndexer = $this->findOrdersIndexer();
        if ($ordersIndexer && !$ordersIndexer->isScheduled()) {
            $ordersIndexer->setScheduled(true);
            $this->logger->info(
                'Mode for indexer sales_order_data_exporter_v2 has been forced to \'Update by Schedule\'.'
            );
        }

        $setup->endSetup();
    }

    /**
     * Finds orders indexer in the set of available indexers
     *
     * @return IndexerInterface|null
     */
    private function findOrdersIndexer(): ?IndexerInterface
    {
        try {
            return $this->indexerInterfaceFactory->create()->load(self::ORDERS_INDEXER_NAME);
        } catch (InvalidArgumentException) {
            // ignored, if not found is expected to do nothing
            return null;
        }
    }
}
