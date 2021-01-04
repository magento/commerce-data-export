<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogExportApi\Api\EntityRequest;

/**
 * Requested entity item data object
 */
class Item
{
    /**
     * @var string
     */
    private $entityId;

    /**
     * @var string[]|null
     */
    private $attributeCodes;

    /**
     * Get requested entity id.
     *
     * @return string
     */
    public function getEntityId(): string
    {
        return $this->entityId;
    }

    /**
     * Set requested entity id.
     *
     * @param string $entityId
     *
     * @return void
     */
    public function setEntityId(string $entityId): void
    {
        $this->entityId = $entityId;
    }

    /**
     * Get requested entity attribute codes.
     *
     * @return string[]|null
     */
    public function getAttributeCodes(): ?array
    {
        return $this->attributeCodes;
    }

    /**
     * Set requested entity attribute codes.
     *
     * @param string[]|null $attributeCodes
     *
     * @return void
     */
    public function setAttributeCodes(?array $attributeCodes): void
    {
        $this->attributeCodes = $attributeCodes;
    }
}
