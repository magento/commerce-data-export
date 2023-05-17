<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Plugin;

use Magento\DataExporter\Model\Indexer\FeedIndexer;
use Magento\Framework\Exception\BulkException;
use Magento\Framework\Mview\Processor;
use Magento\Framework\Mview\View\CollectionFactory;
use Magento\Framework\Mview\ViewInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Phrase;
use Magento\Framework\Exception\RuntimeException;

/**
 * Hot fix before AC-8768
 */
class CoverExceptionMview
{
    private CollectionFactory $viewsFactory;

    /**
     * @param CollectionFactory $viewsFactory
     */
    public function __construct(
        CollectionFactory $viewsFactory
    ) {
        $this->viewsFactory = $viewsFactory;
    }

    /**
     * Don't fail before complete all indexer
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
            try {
                $view->update();
            } catch (\Throwable $e) {
                if ($this->isDataExporterIndexer($view)) {
                    $exception->addException(
                        new RuntimeException(new Phrase('Mview fail %1', [$view->getId()]), $e)
                    );
                } else {
                    throw $e; // keep original behavior for core indexers
                }
            }
        }

        if ($exception->wasErrorAdded()) {
            throw $exception;
        }
    }

    /**
     * Return list of views by group
     *
     * @param string $group
     * @return ViewInterface[]
     */
    private function getViewsByGroup(string $group = ''): array
    {
        $collection = $this->viewsFactory->create();
        return $group ? $collection->getItemsByColumnValue('group', $group) : $collection->getItems();
    }

    private function isDataExporterIndexer(ViewInterface $view): bool
    {
        return is_subclass_of(ObjectManager::getInstance()->get($view->getActionClass()), FeedIndexer::class);
    }
}
