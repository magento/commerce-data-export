<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ConfigurationDataExporter\Event\Data;

/**
 * Data object for config data
 */
class Config
{
    /**
     * @var int
     */
    private $store;

    /**
     * @var string
     */
    private $name;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @param int $store
     * @param string $name
     * @param mixed $value
     */
    public function __construct(int $store, string $name, $value)
    {
        $this->store = $store;
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * Get config store id.
     *
     * @return int
     */
    public function getStore(): int
    {
        return (int)$this->store;
    }

    /**
     * Set config store id.
     *
     * @param int $storeId
     *
     * @return void
     */
    public function setStore(int $storeId): void
    {
        $this->store = $storeId;
    }

    /**
     * Get config path (name).
     *
     * @return string
     */
    public function getName(): string
    {
        return (string)$this->name;
    }

    /**
     * Set config path (name).
     *
     * @param string $name
     *
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get config value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set config value.
     *
     * @param mixed $value
     *
     * @return void
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }
}
