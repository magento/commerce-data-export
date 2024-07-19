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

/**
 * Interface FeedInterface
 */
interface FeedInterface
{
    /**
     * Get feed from given timestamp.
     *
     * If {$ignoredExportStatus} provided returns feed without specified export status
     *
     * @param string $timestamp
     * @param array|null $ignoredExportStatus
     * @return array
     * @throws \Zend_Db_Statement_Exception
     * @see \Magento\DataExporter\Status\ExportStatusCode
     */
    public function getFeedSince(string $timestamp, array $ignoredExportStatus = null): array;

    /**
     * Get feed metadata
     *
     * @return FeedIndexMetadata
     */
    public function getFeedMetadata(): FeedIndexMetadata;
}
