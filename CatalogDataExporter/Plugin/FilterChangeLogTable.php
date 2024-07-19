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

namespace Magento\CatalogDataExporter\Plugin;

use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Setup\Model\FixtureGenerator\SqlCollector;

/**
 * Filter out changelog tables by pattern {*_cl}: fixture generation running in Update on Schedule mode,
 * however it doesn't have knowledge how to hande changelog table
 */
class FilterChangeLogTable
{
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        CommerceDataExportLoggerInterface $logger
    ) {
        $this->logger = $logger;
    }
    /**
     * Filter out changelog tables by pattern {*_cl}
     *
     * @param SqlCollector $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetSql(SqlCollector $subject, array $result): array
    {
        try {
            return array_filter($result, static function ($item) {
                return !str_ends_with($item[1], '_cl');
            });
        } catch (\Throwable $e) {
            $this->logger->error(
                'Data Exporter exception has occurred: ' . $e->getMessage(),
                ['exception' => $e]
            );
            return $result;
        }
    }
}
