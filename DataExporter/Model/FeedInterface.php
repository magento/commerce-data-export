<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
