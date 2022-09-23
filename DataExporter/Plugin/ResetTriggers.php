<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Plugin;

use Magento\Catalog\Model\ResourceModel\Indexer\ActiveTableSwitcher as Subject;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Mview\View\CollectionFactory;
use Magento\Framework\Mview\View\CollectionInterface;
use Magento\Framework\Mview\View\StateInterface;

/**
 * Updating triggers after changing tables. For the purposes of a full reindex,
 * 2 tables are used, index and replace, each time after reindex they change names together with triggers
 */
class ResetTriggers
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var CollectionInterface
     */
    private $viewCollection;

    /**
     * @param CollectionFactory $viewCollectionFactory
     * @param ResourceConnection $resource
     */
    public function __construct(
        CollectionFactory $viewCollectionFactory,
        ResourceConnection $resource
    ) {
        $this->resource = $resource;
        $this->viewCollection = $viewCollectionFactory->create();
    }

    /**
     * Recreate triggers
     *
     * @param Subject $subject
     * @param callable $proceed
     * @param AdapterInterface $connection
     * @param array $tableNames
     * @return void
     * @throws \Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundSwitchTable(
        Subject $subject,
        callable $proceed,
        AdapterInterface $connection,
        array $tableNames
    ) {
        $viewList = $this->getViewsForTables($tableNames);
        foreach ($viewList as $view) {
            $view->unsubscribe();
        }
        $result = $proceed($connection, $tableNames);
        foreach ($viewList as $view) {
            $view->subscribe();
        }
        return $result;
    }

    /**
     * Get list of views that are enabled for particular tables
     * @param $tableNames
     * @return array
     */
    private function getViewsForTables($tableNames): array
    {
        // Get list of views that are enabled
        $allViewList = $this->viewCollection->getViewsByStateMode(StateInterface::MODE_ENABLED);
        $viewList = [];
        $dbPrefix = $this->resource->getTablePrefix();
        foreach ($tableNames as &$tableName) {
            $tableName = preg_replace("/^$dbPrefix/", '', $tableName);
        }

        foreach ($allViewList as $view) {
            $subscriptions = $view->getSubscriptions();
            if (array_intersect(array_keys($subscriptions), $tableNames)) {
                $viewList[] = $view;
            }
        }

        return $viewList;
    }
}
