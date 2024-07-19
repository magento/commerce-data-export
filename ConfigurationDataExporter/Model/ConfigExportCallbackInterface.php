<?php
/**
 * Copyright 2021 Adobe
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
