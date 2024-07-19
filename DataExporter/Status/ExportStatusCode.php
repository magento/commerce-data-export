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

class ExportStatusCode
{
    /**
     * HTTP SUCCESS code value
     */
    private const SUCCESS = 200;

    /**
     * Value of current status code
     * @var int
     */
    private int $statusCode;

    /**
     * @param int $statusCode
     */
    public function __construct(
        int $statusCode
    ) {
        $this->statusCode = $statusCode;
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
     * Return true regardless of the response status code
     *
     * @return bool
     */
    public function isSent(): bool
    {
        return !in_array(
            $this->statusCode,
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
}
