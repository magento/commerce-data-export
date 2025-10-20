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

namespace Magento\DataExporterStatus\Ui\DataProvider;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporterStatus\Service\FeedIndexStatus;
use Magento\DataExporterStatus\Service\SupportedFeedsProvider;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;

class ExportStatusDataProvider extends DataProvider
{
    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param SupportedFeedsProvider $feedListProvider
     * @param array $meta
     * @param array $data
     */
    public function __construct(
                                                $name,
                                                $primaryFieldName,
                                                $requestFieldName,
        ReportingInterface                      $reporting,
        SearchCriteriaBuilder                   $searchCriteriaBuilder,
        RequestInterface                        $request,
        FilterBuilder                           $filterBuilder,
        private readonly SupportedFeedsProvider $feedListProvider,
        array                                   $meta = [],
        array                                   $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        $items = [];
        foreach ($this->feedListProvider->getSupportedFeeds() as $item) {
            /** @var FeedIndexStatus $feedIndexStatus */
            $feedIndexStatus = $item['config'];
            /** @var FeedIndexMetadata $feedMetadata */
            $feedMetadata = $item['metadata'];
            $sourceRecordsQty = $feedIndexStatus->getSourceRecordsQty();
            $successfullySentRecordsQty = $feedIndexStatus->getSuccessfullySentRecordsQty();
            $failedRecordsQty = $feedIndexStatus->getFailedRecordsQty();
            $items[] = [
                'feed_name' => $feedMetadata->getFeedName(),
                'feed_summary' => $feedMetadata->getFeedSummary(),
                'source_records_qty' => $sourceRecordsQty,
                'success_records_qty' => $successfullySentRecordsQty,
                'failed_records_qty' => $failedRecordsQty
            ];
        }
        return ['items' => $items];
    }
}
