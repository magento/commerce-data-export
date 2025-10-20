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
use Magento\Framework\App\ObjectManager;

/**
 * Feed indexer metadata provider
 */
class FeedIndexStatusFactory
{
    private array $feedIndexStatuses;

    /**
     * @param FeedIndexMetadata $feedIndexMetadata
     * @return FeedIndexStatus
     */
    public function getOrCreate(FeedIndexMetadata $feedIndexMetadata): FeedIndexStatus
    {
        if (!isset($this->feedIndexStatuses[$feedIndexMetadata->getFeedName()])) {
            $this->feedIndexStatuses[$feedIndexMetadata->getFeedName()] = ObjectManager::getInstance()->create(
                FeedIndexStatus::class,
                [
                    'feedIndexMetadata' => $feedIndexMetadata,
                ]
            );
        }
        return $this->feedIndexStatuses[$feedIndexMetadata->getFeedName()];
    }
}
