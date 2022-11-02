<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product\Formatter;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class DateFormatter
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
    public function __construct(TimezoneInterface $timezone){
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
