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

namespace Magento\DataExporter\Export;

use Magento\DataExporter\Export\Request\Info;
use Magento\DataExporter\Export\Request\Node;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;

interface DataProcessorInterface
{
    /**
     * Execute data processing
     *
     * @param array $arguments
     * @param callable $dataProcessorCallback
     * @param FeedIndexMetadata $metadata
     * @param Node|null $node
     * @param Info|null $info
     * @return void
     */
    public function execute(
        array $arguments,
        callable $dataProcessorCallback,
        FeedIndexMetadata $metadata,
        Node $node = null,
        Info $info = null
    ): void;
}
