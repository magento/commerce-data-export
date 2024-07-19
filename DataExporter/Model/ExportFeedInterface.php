<?php
/**
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
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;

interface ExportFeedInterface
{
    /**
     * Pass environment variable "PERSIST_EXPORTED_FEED" to enable saving prepared feed record to the DB, for example:
     * PERSIST_EXPORTED_FEED=1 bin/magento saas:resync --feed=products
     *
     * To enable persisting of exported feed permanently, you may add "'PERSIST_EXPORTED_FEED' => 1" to app/etc/env.php
     *
     * Payload will be stored in to the corresponding feed table
     */
    public const PERSIST_EXPORTED_FEED = 'PERSIST_EXPORTED_FEED';

    /**
     * Export data
     *
     * @param array $data
     * @param FeedIndexMetadata $metadata
     * @return FeedExportStatus
     */
    public function export(array $data, FeedIndexMetadata $metadata): FeedExportStatus;
}
