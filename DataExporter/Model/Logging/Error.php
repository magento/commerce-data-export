<?php
/**
 * Copyright 2022 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Logging;

use Monolog\Logger;

/**
 * Log error message for error log level Logger::ERROR and higher
 */
class Error extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/commerce-data-export-errors.log';

    /**
     * @var int
     */
    protected $loggerType = Logger::ERROR;
}
