<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Plugin\Index;

use Magento\CatalogDataExporter\Model\Indexer\IndexInvalidationManager;
use Magento\Store\Model\ResourceModel\Website;

class InvalidateOnWebsiteChange
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
        string $invalidationEvent = 'website_changed'
    ) {
        $this->invalidationManager = $invalidationManager;
        $this->invalidationEvent = $invalidationEvent;
    }

    /**
     * Invalidate on save
     *
     * @param Website $subject
     * @param Website $result
     * @return Website
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(Website $subject, Website $result)
    {
        $this->invalidationManager->invalidate($this->invalidationEvent);
        return $result;
    }

    /**
     * Invalidate on delete
     *
     * @param Website $subject
     * @param Website $result
     * @return Website
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterDelete(Website $subject, Website $result)
    {
        $this->invalidationManager->invalidate($this->invalidationEvent);
        return $result;
    }
}
