<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Plugin\Index;

use Magento\CatalogDataExporter\Model\Indexer\IndexInvalidationManager;
use Magento\Store\Model\ResourceModel\Store;

/**
 * Class InvalidateOnGroupChange
 *
 * Invalidates indexes on Store change
 */
class InvalidateOnStoreChange
{
    /**
     * @var IndexInvalidationManager
     */
    private $invalidationManager;

    /**
     * @var string
     */
    private $invalidationEvent;

    /**
     * InvalidateOnChange constructor.
     *
     * @param IndexInvalidationManager $invalidationManager
     * @param string $invalidationEvent
     */
    public function __construct(
        IndexInvalidationManager $invalidationManager,
        string $invalidationEvent = 'group_changed'
    ) {
        $this->invalidationManager = $invalidationManager;
        $this->invalidationEvent = $invalidationEvent;
    }

    /**
     * Invalidate on save
     *
     * @param Store $subject
     * @param Store $result
     * @return Store
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(Store $subject, Store $result)
    {
        $this->invalidationManager->invalidate($this->invalidationEvent);
        return $result;
    }

    /**
     * Invalidate on delete
     *
     * @param Store $subject
     * @param Store $result
     * @return Store
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterDelete(Store $subject, Store $result)
    {
        $this->invalidationManager->invalidate($this->invalidationEvent);
        return $result;
    }
}
