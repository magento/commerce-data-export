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

use Magento\DataExporter\Status\ExportStatusCode;

/**
 * DTO class to handle result of feed export
 */
class FeedExportStatus
{
    private ExportStatusCode $status;
    private string $reasonPhrase;
    private array $failedItems;

    /**
     * @param ExportStatusCode $status
     * @param string $reasonPhrase
     * @param array $failedItems
     */
    public function __construct(
        ExportStatusCode $status,
        string $reasonPhrase,
        array $failedItems
    ) {
        $this->status = $status;
        $this->reasonPhrase = $reasonPhrase;
        $this->failedItems = $failedItems;
    }

    /**
     * Get reason phrase
     *
     * @return string
     */
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    /**
     * Get failed items
     *
     * @return array
     */
    public function getFailedItems(): array
    {
        return $this->failedItems;
    }

    /**
     * Get status
     *
     * @return ExportStatusCode
     */
    public function getStatus(): ExportStatusCode
    {
        return $this->status;
    }
}
