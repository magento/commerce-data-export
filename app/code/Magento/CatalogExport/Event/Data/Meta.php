<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogExport\Event\Data;

/**
 * MetaData object for changed entities
 */
class Meta
{
    /**
     * @var null|string
     */
    private $scope;

    /**
     * @var string
     */
    private $eventType;

    /**
     * Get scope for changed entities
     *
     * @return string|null
     */
    public function getScope(): ?string
    {
        return $this->scope;
    }

    /**
     * Set scope for changed entities
     *
     * @param null|string $scope
     * @return void
     */
    public function setScope(string $scope = null): void
    {
        $this->scope = $scope;
    }

    /**
     * Get changed entities event type
     *
     * @return string
     */
    public function getEventType(): string
    {
        return $this->eventType;
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
