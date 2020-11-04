<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogExportApi\Api;

use Magento\CatalogExportApi\Api\EntityRequest\Item;

/**
 * Requested entities data object
 */
class EntityRequest
{
    /**
     * @var Item[]
     */
    private $entities;

    /**
     * @var string[]|null
     */
    private $storeViewCodes;

    /**
     * Get request entities.
     *
     * @return \Magento\CatalogExportApi\Api\EntityRequest\Item[]
     */
    public function getEntities(): array
    {
        return $this->entities;
    }

    /**
     * Set request entities.
     *
     * @param \Magento\CatalogExportApi\Api\EntityRequest\Item[] $entities
     *
     * @return void
     */
    public function setEntities(array $entities): void
    {
        $this->entities = $entities;
    }

    /**
     * Get request store view codes
     *
     * @return string[]|null
     */
    public function getStoreViewCodes(): ?array
    {
        return $this->storeViewCodes;
    }

    /**
     * Set request store view codes.
     *
     * @param string[]|null $storeViewCodes
     *
     * @return void
     */
    public function setStoreViewCodes(?array $storeViewCodes): void
    {
        $this->storeViewCodes = $storeViewCodes;
    }
}
