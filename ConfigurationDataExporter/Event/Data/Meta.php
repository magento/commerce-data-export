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

/**
 * MetaData object for changed config
 */
class Meta
{
    /**
     * @var string
     */
    private $event;

    /**
     * @param string $event
     */
    public function __construct(string $event)
    {
        $this->event = $event;
    }

    /**
     * Get changed config event type
     *
     * @return string
     */
    public function getEvent(): string
    {
        return (string)$this->event;
    }

    /**
     * Set eventType for changed entities
     *
     * @param string $event
     * @return void
     */
    public function setEvent(string $event): void
    {
        $this->event = $event;
    }
}
