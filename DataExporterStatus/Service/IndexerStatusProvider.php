<?php
/**
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2025 Adobe
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

namespace Magento\DataExporterStatus\Service;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Service\FeedIndexerProvider;
use Magento\DataExporterStatus\Service\DTO\IndexerStatus;
use Magento\Framework\Mview\View\ChangelogTableNotExistsException;
use Magento\Framework\Mview\ViewInterface;

class IndexerStatusProvider
{
    public function __construct(
      private readonly FeedIndexerProvider $feedIndexerProvider,
    ) {
    }

    public function getIndexerStatus(FeedIndexMetadata $metadata): IndexerStatus
    {
        $indexer = $this->feedIndexerProvider->getIndexer($metadata);
        if ($indexer === null) {
            throw new \RuntimeException(sprintf('No indexer found for feed %1', $metadata->getFeedName()));
        }
        [$idsN, $ids, $updateDate] = $this->getChangelog($indexer->getView());
        return new IndexerStatus(
            $indexer,
            $idsN,
            $ids,
            $updateDate
        );

    }

    /**
     * Returns the pending count of the view
     *
     * @param ViewInterface $view
     * @return array
     */
    private function getChangelog(ViewInterface $view): array
    {
        $changelog = $view->getChangelog();

        try {
            $currentVersionId = $changelog->getVersion();
        } catch (ChangelogTableNotExistsException $e) {
            return [0, [], $e->getMessage()];
        }

        $state = $view->getState();
        $ids = $changelog->getList($state->getVersionId(), $currentVersionId);

        return [count($ids), $ids, $view->getUpdated()];
    }
}
