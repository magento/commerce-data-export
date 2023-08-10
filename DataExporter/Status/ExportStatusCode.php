<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
     * Get value
     *
     * @return int
     */
    public function getValue(): int
    {
        return $this->statusCode;
    }
}
