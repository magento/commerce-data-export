<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
