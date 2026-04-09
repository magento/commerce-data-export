<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Integration\Stub;

use Magento\DataExporter\Model\ExportFeedInterface;
use Magento\DataExporter\Model\FeedExportStatus;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Status\ExportStatusCode;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Export feed stub with controllable failure mode for integration tests.
 *
 * Set $shouldFail = true before an operation to simulate a server-side error (503 → status=RETRYABLE=2).
 * Call reset() in tearDown to restore defaults.
 */
class ControllableExportFeedStub implements ExportFeedInterface
{
    /**
     * When true the stub returns HTTP 503, which maps to ExportStatusCodeProvider::RETRYABLE (2).
     */
    public static bool $shouldFail = false;

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function export(array $data, FeedIndexMetadata $metadata): FeedExportStatus
    {
        // 503 is not in [200, 400, 2, -1] so ExportStatusCode::mapStatus() converts it to RETRYABLE(2).
        $statusCode = Bootstrap::getObjectManager()->create(
            ExportStatusCode::class,
            ['statusCode' => self::$shouldFail ? 503 : 200]
        );

        print_r($statusCode);
        return Bootstrap::getObjectManager()->create(
            FeedExportStatus::class,
            [
                'status'       => $statusCode,
                'reasonPhrase' => self::$shouldFail ? 'Service Unavailable' : '',
                'failedItems'  => [],
            ]
        );
    }

    /**
     * Reset all static state. Call in tearDown to prevent cross-test contamination.
     */
    public static function reset(): void
    {
        self::$shouldFail = false;
    }
}
