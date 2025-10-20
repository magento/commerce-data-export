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

namespace Magento\DataExporterStatus\Ui\DataProvider\Listing;

use Magento\DataExporter\Model\FeedInterface;
use Magento\DataExporter\Model\FeedPool;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporterStatus\Service\IndexerStatusProvider;
use Magento\DataExporterStatus\Service\StatusHandler;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Escaper;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Indexer\StateInterface;
use Magento\Framework\Phrase;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;

/**
 * Prepare collection to display feed submission status in admin grid
 * Items with deleted status are shown only if error occurred during sync process.
 *
 * Customer intentionally cannot filter by deleted status
 */
class Collection extends SearchResult
{
    private string $feedName;
    private FeedInterface $feed;

    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        private readonly FeedPool $feedPool,
        private readonly Context $context,
        private readonly IndexerStatusProvider $indexerStatusProvider
    ) {
        $this->feedName = $this->context->getRequest()->getParam('feed', '');
        $filters = $this->context->getRequest()->getParam('filters');
        if ($filters) {
            if (isset($filters['feed'])) {
                $this->feedName = $filters['feed'];
            }
        }

        try {
            $this->feed = $this->feedPool->getFeed($this->feedName);
        } catch (\Exception $e) {
            $this->feed = array_values($this->feedPool->getList())[0];
            $this->feedName = $this->feed->getFeedMetadata()->getFeedName();
        }

        $mainTable = $this->feed->getFeedMetadata()->getFeedTableName();

        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable);
    }

    /**
     * Override _initSelect to add custom columns and data
     *
     * @return void
     */
    protected function _initSelect()
    {
        $feedMetadata = $this->feed->getFeedMetadata();
        $sourceTable = $this->getTable($feedMetadata->getSourceTableName());
        $sourceTableIdField = $feedMetadata->getSourceTableIdentityField();
        $feedTableIdField = FeedIndexMetadata::FEED_TABLE_FIELD_SOURCE_ENTITY_ID;


        $this->getSelect()
            ->from(
                ['main_table' => $this->getMainTable()],
                []
            )->joinLeft(
                ['source_table' => $sourceTable],
                \sprintf(
                    'main_table.%1$s = source_table.%2$s',
                    $feedTableIdField,
                    $sourceTableIdField
                ),
                []
            )->columns([
                'feed' => new Expression("'$this->feedName'"),
                'source_entity_id' => 'main_table.' . $feedTableIdField,
                'id' => 'main_table.id',
                'feed_data' => 'main_table.feed_data',
                'metadata' => 'main_table.metadata',
                'errors' => 'main_table.errors',
                'status' => 'main_table.status',
                'is_deleted' => 'main_table.is_deleted',
                'modified_at' => 'main_table.modified_at',
                'entity_exists' => new Expression(
                    \sprintf(
                        'IF(source_table.%s IS NULL, 0, 1)',
                        $sourceTableIdField
                    )
                )
            ]);

        $filterStatus = $this->getStatusFilterValue();
        if ($filterStatus) {
            if ($filterStatus == StatusHandler::STATUS_AWAITING_SUBMISSION) {
                $state = $this->indexerStatusProvider->getIndexerStatus($feedMetadata);
                $idsForResubmission = $state->getIndexer()->getStatus() !== StateInterface::STATUS_INVALID
                    ? $state->getChangelogIds()
                    : null; // all items are awaiting submission if indexer is invalid

                if ($idsForResubmission !== null) {
                    if (count($idsForResubmission) > 0) {
                        $this->getSelect()->where(
                            'source_table.' . $sourceTableIdField . ' IN (?)',
                            $idsForResubmission
                        );
                    } else {
                        // No items are awaiting submission - add a filter that results in an empty set
                        $this->getSelect()->where('1 = 0');
                    }
                }
            } else {
                StatusHandler::applyFilterFromUiStatus($filterStatus, $this->getSelect());
            }
        } else {
            $this->getSelect()
                ->where('main_table.is_deleted = 0')
                ->orWhere('main_table.is_deleted = 1 AND main_table.status != 200');
        }
    }

    private function getStatusFilterValue(): string|int|null
    {
        $filters = $this->context->getRequest()->getParam('filters');
        return $filters['status'] ?? null;
    }

    /**
     * Get collection data with proper error handling and escaping
     *
     * @return array
     */
    public function getData()
    {
        try {
            $data = parent::getData();
            $feedMetadata = $this->feed->getFeedMetadata();
            $state = $this->indexerStatusProvider->getIndexerStatus($feedMetadata);
            $origStatusLabels = StatusHandler::getOrigStatusLabels();

            if (is_array($data)) {
                foreach ($data as &$item) {
                    $item = $this->addErrorsField($item, $origStatusLabels);
                    $item['status_orig'] = $origStatusLabels[$item['status']]
                        ?? $this->unrecognizedStatus($item['status']);
                    $item['status'] = StatusHandler::feedItemStatusToUIStatus((int)$item['status']);
                    if ($state->getChangelogBacklog()
                        && $state->getIndexer()->getStatus() !== StateInterface::STATUS_INVALID) {
                        if (in_array(
                            (int)$item[FeedIndexMetadata::FEED_TABLE_FIELD_SOURCE_ENTITY_ID],
                            $state->getChangelogIds())
                        ) {
                            $item['status'] = StatusHandler::STATUS_AWAITING_SUBMISSION;
                        }
                    }
                }
            }

            return $data;
        } catch (\Exception $e) {
            $this->_logger->error('Error retrieving feed status data for the Feed Submission Status greed: '
                . $e->getMessage(), [
                    'exception' => $e,
                    'feed_name' => $this->feedName,
                    'trace' => $e->getTraceAsString()
                ]
            );

            throw  new \RuntimeException('An error occurred while loading the feed status data. Please check the logs for more details.');
        }
    }

    private function unrecognizedStatus($status): Phrase
    {
        return __('Status code: %1', $status);
    }

    /**
     * Escape item data to prevent XSS attacks
     *
     * @param array $item
     * @param array $origStatusLabels
     * @return array
     */
    private function addErrorsField(array $item, array $origStatusLabels): array
    {
        $error = [];
        if (StatusHandler::isFailedStatus((int)$item['status'])) {
            $error[] = __('Status: ') . ($origStatusLabels[$item['status']]
                ?? $this->unrecognizedStatus($item['status']));
        }
        if (isset($item['errors']) && \is_string($item['errors'])) {
            $error[] = explode(PHP_EOL, $item['errors']);
        }
        if (!empty($error)) {
            $item['errors'] = $error;
        }
        return $item;
    }
}
