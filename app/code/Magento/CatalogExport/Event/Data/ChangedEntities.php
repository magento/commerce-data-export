<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogExport\Event\Data;

/**
 * Changed entities object
 */
class ChangedEntities
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
     * @param \Magento\CatalogExport\Event\Data\Meta $meta
     * @param \Magento\CatalogExport\Event\Data\Data $data
     */
    public function __construct(Meta $meta, Data $data)
    {
        $this->meta = $meta;
        $this->data = $data;
    }

    /**
     * Get changed entities metadata
     *
     * @return \Magento\CatalogExport\Event\Data\Meta
     */
    public function getMeta(): Meta
    {
        return $this->meta;
    }

    /**
     * Get changed entities data
     *
     * @return \Magento\CatalogExport\Event\Data\Data
     */
    public function getData(): Data
    {
        return $this->data;
    }
}
