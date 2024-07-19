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

namespace Magento\DataExporter\Model\Logging;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;

/**
 * Interface used to provide custom log handlers defined in di.xml
 */
interface CommerceDataExportLoggerInterface extends \Psr\Log\LoggerInterface {
    /**
     * Pass environment variable "EXPORTER_PROFILER" to enable profiler, for example:
     * EXPORTER_PROFILER=1 bin/magento index:reindex catalog_data_exporter_products
     *
     * Profiler data will be stored in var/log/commerce-data-export.log in format:
     * Provider class name, processed entities, execution time, memory consumption
     */
    public const EXPORTER_PROFILER = 'EXPORTER_PROFILER';

    public const LOG_PROGRESS_INTERVAL = 'EXPORTER_LOG_PROGRESS_INTERVAL';

    /**
     * @param FeedIndexMetadata $feedIndexMetadata
     * @param string $operation
     * @param bool $logMessage
     * @return void
     */
    public function initSyncLog(FeedIndexMetadata $feedIndexMetadata, string $operation, bool $logMessage = true): void;

    /**
     * @param array $context
     * @return void
     */
    public function addContext(array $context): void;

    /**
     * Log full/partial sync/reindex process progress.
     * Calling method without arguments is used to track # of completed iterations
     *
     * @param $processedIdsNumber - number of processed ids for source entity, e.g. # of products ids
     * @param $syncedItems - number of items delivered to SaaS
     * @return void
     */
    public function logProgress($processedIdsNumber = null, $syncedItems = null): void;

    /**
     * @return void
     */
    public function complete(): void;
}