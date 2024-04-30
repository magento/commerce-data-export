<?php
/**
 * Copyright 2023 Adobe
 * All rights reserved
 *
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Setup;

use Magento\DataExporter\Model\Indexer\FeedIndexer;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;
use Magento\Framework\Indexer\ActionFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Indexer\Model\Indexer\Collection;

/**
 * Set all data exporter indexers to 'Update by Schedule' mode on upgrade.
 */
class Recurring implements \Magento\Framework\Setup\InstallSchemaInterface
{
    /**
     * @var SchemaSetupInterface
     */
    private SchemaSetupInterface $schemaSetup;

    /**
     * @var LoggerInterface;
     */
    private LoggerInterface $logger;

    /**
     * @var Collection
     */
    private $indexerCollection;

    /**
     * @var ActionFactory
     */
    private $actionFactory;

    /**
     * @param SchemaSetupInterface $schemaSetup
     * @param LoggerInterface $logger
     * @param Collection $indexerCollection
     * @param ActionFactory $actionFactory
     */
    public function __construct(
        SchemaSetupInterface $schemaSetup,
        LoggerInterface $logger,
        Collection $indexerCollection,
        ActionFactory $actionFactory
    ) {
        $this->schemaSetup = $schemaSetup;
        $this->logger = $logger;
        $this->indexerCollection = $indexerCollection;
        $this->actionFactory = $actionFactory;
    }

    /**
     * @inheritdoc
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->schemaSetup->startSetup();

        $indexers = $this->indexerCollection->getItems();

        foreach ($indexers as $indexer) {
            try {
                $indexerAction =  $this->actionFactory->create($indexer->getActionClass());

                if ($indexerAction instanceof FeedIndexer && $indexer->isScheduled() === false) {
                    $this->logger->info(
                        sprintf("Setting mode Update On Schedule for indexer %s", $indexer->getTitle())
                    );
                    $indexer->setScheduled(true);
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        $this->schemaSetup->endSetup();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }
}
