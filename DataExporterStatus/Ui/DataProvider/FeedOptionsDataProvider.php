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

use Magento\DataExporterStatus\Service\SupportedFeedsProvider;
use Magento\Framework\Data\OptionSourceInterface;

class FeedOptionsDataProvider implements OptionSourceInterface
{

    public function __construct(readonly private SupportedFeedsProvider $feedListProvider)
    {
    }

    /**
     * Return array of options for feed selector
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];

        foreach ($this->feedListProvider->getSupportedFeeds() as $feed) {
            $feedMetadata = $feed['metadata'];
            $feedName = $feedMetadata->getFeedName();
            $options[] = [
                'value' => $feedName,
                'label' => $feedMetadata->getFeedSummary() ?: ucfirst($feedName)
            ];
        }

        return $options;
    }
}