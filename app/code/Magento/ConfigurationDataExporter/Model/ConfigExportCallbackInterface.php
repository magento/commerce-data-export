<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ConfigurationDataExporter\Model;

/**
 * Perform export of system configuration
 */
interface ConfigExportCallbackInterface
{
    const EVENT_TYPE_FULL = 'config_export_full';
    const EVENT_TYPE_UPDATE = 'config_export_update';

    /**
     * Execute callback
     *
     * @param string $evenType
     * @param array $configData
     *
     * @return void
     */
    public function execute(string $evenType, array $configData = []) : void;
}
