<?php
/**
 * Copyright 2022 Adobe
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

namespace Magento\CatalogDataExporter\Model\Provider\Product\Formatter;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Date formatter for product data
 */
class DateFormatter implements FormatterInterface
{
    private const INVALID_DATE = '0000-00-00 00:00:00';

    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $timezone;

    /**
     * @param TimezoneInterface $timezone
     */
    public function __construct(TimezoneInterface $timezone)
    {
        $this->timezone = $timezone;
    }

    /**
     * Format date
     *
     * @param array $row
     * @return array
     */
    public function format(array $row): array
    {
        $now = $this->timezone->date()->format('Y-m-d H:i:s');
        if (isset($row['createdAt']) && $row['createdAt'] == self::INVALID_DATE) {
            $row['createdAt'] = $now;
        }

        if (isset($row['updatedAt']) && $row['updatedAt'] == self::INVALID_DATE) {
            $row['updatedAt'] = $now;
        }

        return $row;
    }
}
