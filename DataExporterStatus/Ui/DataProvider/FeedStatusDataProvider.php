<?php
/**
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2025 Adobe
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

namespace Magento\DataExporterStatus\Ui\DataProvider;

use Magento\Framework\Api\Filter;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;

/**
 * Class FeedStatusDataProvider - Extends the default DataProvider to handle 'feed' parameter
 */
class FeedStatusDataProvider extends DataProvider
{
    /**
     * @param Filter $filter
     * @return void
     */
    public function addFilter(Filter $filter): void
    {
        if (!in_array($filter->getField(), ['feed', 'is_deleted', 'status'], true)) {
            parent::addFilter($filter);
        }
    }

    /**
     * @return array
     */
    public function getData()
    {
        $data = parent::getData();
        $feedName = $this->getCurrentFeedName();

        // Add feed name to the data array so it's available to UI components
        $data['feed'] = $feedName;

        return $data;
    }

    /**
     * Get current feed name from request parameters
     */
    private function getCurrentFeedName(): string
    {
        // Try to get feed from request parameter
        $feedName = $this->request->getParam('feed', '');

        return $feedName ?? '';
    }
}
