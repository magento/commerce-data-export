<?php
/*************************************************************************
 *
 * Copyright 2023 Adobe
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
 * ***********************************************************************
 */
declare(strict_types=1);

namespace Magento\DataExporter\Plugin;

use Magento\DataExporter\Model\Indexer\ViewMaterializer;
use Magento\DataExporter\Model\Indexer\FeedIndexer;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\Exception\BulkException;
use Magento\Framework\Mview\Processor;
use Magento\Framework\Mview\View\CollectionFactory;
use Magento\Framework\Mview\ViewInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Phrase;
use Magento\Framework\Exception\RuntimeException;

class MviewUpdatePlugin
{
    private CollectionFactory $viewsFactory;
    private ViewMaterializer $viewMaterializer;
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @param CollectionFactory $viewsFactory
     * @param ViewMaterializer $viewMaterializer
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        CollectionFactory $viewsFactory,
        ViewMaterializer $viewMaterializer,
        CommerceDataExportLoggerInterface $logger
    ) {
        $this->viewsFactory = $viewsFactory;
        $this->viewMaterializer = $viewMaterializer;
        $this->logger = $logger;
    }

    /**
     * Run custom mview::update logic for commerce data export indexers.
     *
     * @param Processor $subject
     * @param callable $proceed
     * @param string $group
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws BulkException
     * @throws \Throwable
     */
    public function aroundUpdate(Processor $subject, callable $proceed, $group = ''): void
    {
        $exception = new BulkException();
        $views = $this->getViewsByGroup($group);
        foreach ($views as $view) {
            if ($this->isDataExporterIndexer($view)) {
                try {
                    $this->viewMaterializer->execute($view);
                } catch (\Throwable $e) {
                    // Hot fix before AC-8768
                    $exception->addException(
                        new RuntimeException(new Phrase('Mview fail %1', [$view->getId()]), $e)
                    );
                }
            } else {
                $view->update();
            }
        }

        if ($exception->wasErrorAdded()) {
            foreach ($exception->getErrors() as $e) {
                $this->logger->error(
                    'Data Exporter exception has occurred: ' . $e->getMessage(),
                    ['exception' => $e]
                );
            }
            throw $exception;
        }
    }

    /**
     * Returns list of views by group
     *
     * @param string $group
     * @return ViewInterface[]
     */
    private function getViewsByGroup(string $group = ''): array
    {
        $collection = $this->viewsFactory->create();
        return $group ? $collection->getItemsByColumnValue('group', $group) : $collection->getItems();
    }

    /**
     * Checks if view is data exporter indexer.
     *
     * @param ViewInterface $view
     * @return bool
     */
    private function isDataExporterIndexer(ViewInterface $view): bool
    {
        return is_subclass_of(ObjectManager::getInstance()->get($view->getActionClass()), FeedIndexer::class);
    }
}
