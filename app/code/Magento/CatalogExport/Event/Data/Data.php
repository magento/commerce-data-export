<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogExport\Event\Data;

/**
 * Data object for changed entities
 */
class Data
{
    /**
     * @var Entity[]
     */
    private $entities;

    /**
     * Get entities.
     *
     * @return \Magento\CatalogExport\Event\Data\Entity[]
     */
    public function getEntities(): array
    {
        return $this->entities;
    }

    /**
     * Set entities.
     *
     * @param \Magento\CatalogExport\Event\Data\Entity[] $entities
     *
     * @return void
     */
    public function setEntities(array $entities): void
    {
        $this->entities = $entities;
    }
}
