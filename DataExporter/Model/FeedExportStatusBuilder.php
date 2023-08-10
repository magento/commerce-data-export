<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model;

use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\DataExporter\Status\ExportStatusCode;
use Magento\DataExporter\Status\ExportStatusCodeFactory;

/**
 * Build FeedExportStatus class
 */
class FeedExportStatusBuilder
{
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @var FeedExportStatusFactory
     */
    private FeedExportStatusFactory $feedExportStatusFactory;

    /**
     * @var ExportStatusCodeFactory
     */
    private ExportStatusCodeFactory $exportStatusCodeFactory;

    /**
     * @param FeedExportStatusFactory $feedExportStatusFactory
     * @param ExportStatusCodeFactory $exportStatusCodeFactory
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        FeedExportStatusFactory $feedExportStatusFactory,
        ExportStatusCodeFactory $exportStatusCodeFactory,
        CommerceDataExportLoggerInterface $logger
    ) {
        $this->feedExportStatusFactory = $feedExportStatusFactory;
        $this->exportStatusCodeFactory = $exportStatusCodeFactory;
        $this->logger = $logger;
    }

    /**
     * Build data
     *
     * @param int $status
     * @param string $reasonPhrase
     * @param array $failedItems
     * @return FeedExportStatus
     */
    public function build(
        int $status,
        string $reasonPhrase = '',
        array $failedItems = []
    ) : FeedExportStatus {
        try {
            return $this->feedExportStatusFactory->create(
                [
                    'status' => $this->buildStatusCode($status),
                    'reasonPhrase' => $reasonPhrase,
                    'failedItems' => $failedItems
                ]
            );

        } catch (\Throwable $e) {
            $this->logger->error(
                'Data Exporter exception has occurred: ' . $e->getMessage(),
                ['exception' => $e]
            );
            throw new \RuntimeException('Unable to instantiate Feed Export Status');
        }
    }

    /**
     * Build status code
     *
     * @param int $statusCode
     * @return ExportStatusCode
     */
    private function buildStatusCode(int $statusCode) : ExportStatusCode
    {
        try {
            return $this->exportStatusCodeFactory->create(['statusCode' => $statusCode]);

        } catch (\Throwable $e) {
            $this->logger->error(
                'Data Exporter exception has occurred: ' . $e->getMessage(),
                ['exception' => $e]
            );
            throw new \RuntimeException('Unable to instantiate Export Status Code');
        }
    }
}
