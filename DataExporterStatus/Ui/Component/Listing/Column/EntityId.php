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

namespace Magento\DataExporterStatus\Ui\Component\Listing\Column;

use Magento\DataExporter\Model\FeedMetadataPool;
use Magento\DataExporterStatus\Service\FeedIndexStatusFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class Actions
 */
class EntityId extends Column
{
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param FeedMetadataPool $feedMetadataPool
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface                        $context,
        UiComponentFactory                      $uiComponentFactory,
        private readonly FeedMetadataPool       $feedMetadataPool,
        private readonly FeedIndexStatusFactory $feedIndexStatusFactory,
        array                                   $components = [],
        array                                   $data = [],
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        $dataSource = parent::prepareDataSource($dataSource);

        $metadata = null;
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {
                $metadata = $metadata ?? $this->feedMetadataPool->getMetadata($item['feed']);
                $feedIndexStatus = $this->feedIndexStatusFactory->getOrCreate($metadata);

                if (!isset($item[$fieldName])) {
                    $feedData = \json_decode($item['feed_data'], true);
                    if (\is_array($feedData) && isset($feedData[$metadata->getFeedIdentity()])) {
                        $item[$fieldName] = $feedData[$metadata->getFeedIdentity()];
                    }
                }

                if (isset($item[$fieldName]) && (bool)$item['entity_exists'] === true) {
                    $route = $feedIndexStatus->getEntityRoute();
                    if ($route !== null && isset($route['entity_id'], $route['route'])) {
                        $item['link'] = $this->context->getUrl(
                            $route['route'],
                            [$route['entity_id'] => $item[$fieldName]]
                        );
                    }
                }
            }
        }

        return $dataSource;
    }
}
