<?php

/**
 * Copyright 2023 Adobe
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

namespace Magento\CatalogDataExporter\Plugin\Index;

use Magento\CatalogDataExporter\Model\Indexer\IndexInvalidationManager;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Store\Model\ResourceModel\Website;

class InvalidateOnWebsiteChange
{
    private IndexInvalidationManager $invalidationManager;
    private string $invalidationEvent;
    private CommerceDataExportLoggerInterface $logger;

    /**
     * InvalidateOnChange constructor.
     *
     * @param IndexInvalidationManager $invalidationManager
     * @param CommerceDataExportLoggerInterface $logger
     * @param string $invalidationEvent
     */
    public function __construct(
        IndexInvalidationManager $invalidationManager,
        CommerceDataExportLoggerInterface $logger,
        string $invalidationEvent = 'website_changed'
    ) {
        $this->invalidationManager = $invalidationManager;
        $this->invalidationEvent = $invalidationEvent;
        $this->logger = $logger;
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
    public function afterSave(Website $subject, Website $result): Website
    {
        return $this->invalidate($result);
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
    public function afterDelete(Website $subject, Website $result): Website
    {
        return $this->invalidate($result);
    }

    /**
     * @param Website $result
     * @return Website
     */
    private function invalidate(Website $result): Website
    {
        try {
            $this->invalidationManager->invalidate($this->invalidationEvent);
        } catch (\Throwable $e) {
            $this->logger->error(
                'Data Exporter exception has occurred: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
        return $result;
    }
}
