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

namespace Magento\ConfigurationDataExporter\Event\Data;

use Magento\ConfigurationDataExporter\Event\Data\Meta;
use Magento\ConfigurationDataExporter\Event\Data\Data;

/**
 * Changed config object
 */
class ChangedConfig
{
    /**
     * @var Data
     */
    private $data;

    /**
     * @var Meta
     */
    private $meta;

    /**
     * @param \Magento\ConfigurationDataExporter\Event\Data\Meta $meta
     * @param \Magento\ConfigurationDataExporter\Event\Data\Data $data
     */
    public function __construct(Meta $meta, Data $data)
    {
        $this->meta = $meta;
        $this->data = $data;
    }

    /**
     * Get changed config metadata
     *
     * @return \Magento\ConfigurationDataExporter\Event\Data\Meta
     */
    public function getMeta(): Meta
    {
        return $this->meta;
    }

    /**
     * Get changed config data
     *
     * @return \Magento\ConfigurationDataExporter\Event\Data\Data
     */
    public function getData(): Data
    {
        return $this->data;
    }
}
