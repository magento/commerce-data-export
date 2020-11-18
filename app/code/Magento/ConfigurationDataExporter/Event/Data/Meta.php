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
    private $eventType;

    /**
     * Get changed config event type
     *
     * @return string
     */
    public function getEventType(): string
    {
        return (string)$this->eventType;
    }

    /**
     * Set eventType for changed entities
     *
     * @param string $eventType
     * @return void
     */
    public function setEventType(string $eventType): void
    {
        $this->eventType = $eventType;
    }
}
