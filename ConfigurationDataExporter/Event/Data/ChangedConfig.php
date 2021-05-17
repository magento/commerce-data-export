<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
