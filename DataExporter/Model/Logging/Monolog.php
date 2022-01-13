<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Logging;

/**
 * Proxy class to allow instantiate custom Logger Interface
 */
class Monolog extends \Magento\Framework\Logger\Monolog implements CommerceDataExportLoggerInterface {
}