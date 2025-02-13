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

namespace Magento\DataExporter\Status;

/**
 * Original status (HTTP status or application) code is mapped to one of the following Export Status codes:
 *  - HTTP status code 200 (success)
 *  - HTTP status code 400 (user error)
 *  - Application status "skip submission ("-1"), @see ExportStatusCodeProvider::FEED_SUBMIT_SKIPPED
 *  - Application status "retryable ("2"), @see ExportStatusCodeProvider::RETRYABLE
 *
 * Note, any HTTP status code different from 200 or 400 will be considered as retryable
 */
class ExportStatusCode
{
    /**
     * HTTP SUCCESS code value
     */
    private const SUCCESS = 200;

    /**
     * Export operation status code
     * @var int
     */
    private int $statusCode;

    /**
     * Keeps original status code (HTTP status or application status code)
     *
     * @var int
     */
    private int $originalStatusCode;

    /**
     * @param int $statusCode
     */
    public function __construct(
        int $statusCode
    ) {
        $this->originalStatusCode = $statusCode;
        $this->statusCode = $this->mapStatus($statusCode);
    }

    /**
     * Check is success
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return self::SUCCESS === $this->statusCode;
    }

    /**
     * Return true if data was sent outside AC regardless of the response status code
     *
     * @return bool
     */
    public function isSent(): bool
    {
        return !in_array(
            $this->originalStatusCode,
            [
                ExportStatusCodeProvider::APPLICATION_ERROR,
                ExportStatusCodeProvider::FEED_SUBMIT_SKIPPED
            ],
            true
        );
    }

    /**
     * Get value
     *
     * @return int
     */
    public function getValue(): int
    {
        return $this->statusCode;
    }

    /**
     * @param int $statusCode
     * @return int
     */
    private function mapStatus(int $statusCode): int
    {
        return in_array($statusCode, ExportStatusCodeProvider::NON_RETRYABLE_HTTP_STATUS_CODE, true)
        || in_array(
            $statusCode,
            [ExportStatusCodeProvider::RETRYABLE, ExportStatusCodeProvider::FEED_SUBMIT_SKIPPED],
            true
        )
            ? $statusCode
            : ExportStatusCodeProvider::RETRYABLE;
    }
}
