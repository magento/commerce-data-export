<?php
/**
 * Copyright 2024 Adobe
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

namespace Magento\DataExporter\Model;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;

/**
 * Default implementation of ExportFeedInterface.
 *
 * Will throw an exception as it would be used only if SaaS Exporter will not be installed
 */
class ExportFeedDummy implements ExportFeedInterface
{
    /**
     * @inheritdoc
     * @param array $data
     * @param FeedIndexMetadata $metadata
     * @return FeedExportStatus
     */
    public function export(array $data, FeedIndexMetadata $metadata): FeedExportStatus
    {
        throw new \RuntimeException('\Magento\DataExporter\Model\ExportFeedInterface unimplemented');
    }
}
