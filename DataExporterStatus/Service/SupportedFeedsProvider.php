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

use Magento\DataExporter\Model\FeedMetadataPool;

/**
 * Feed indexer metadata provider
 */
class SupportedFeedsProvider
{
    /**
     * @param FeedMetadataPool $feedMetadataPool
     * @param FeedIndexStatusFactory $feedIndexStatusFactory
     */
    public function __construct(
        private readonly FeedMetadataPool $feedMetadataPool,
        private readonly FeedIndexStatusFactory $feedIndexStatusFactory,
    ) {
    }

    /**
     * @return array
     */
    public function getSupportedFeeds(): array
    {
        $items = [];
        foreach ($this->feedMetadataPool->getAll() as $feedMetadata) {
            $feedIndexStatus = $this->feedIndexStatusFactory->getOrCreate($feedMetadata);
            if ($feedIndexStatus->isSupported()) {
                $items[] = [
                    'metadata' => $feedMetadata,
                    'config' => $feedIndexStatus
                ];
            }
        }
        return $items;
    }
}
