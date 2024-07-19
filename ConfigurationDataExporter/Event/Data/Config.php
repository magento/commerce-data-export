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
