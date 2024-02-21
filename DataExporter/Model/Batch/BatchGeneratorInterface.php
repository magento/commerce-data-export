<?php
/************************************************************************
 *
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
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Batch;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;

interface BatchGeneratorInterface
{
    /**
     * Creates data batches based on feed index metadata.
     *
     * @param FeedIndexMetadata $metadata
     * @param array $args
     * @return BatchIteratorInterface
     */
    public function generate(FeedIndexMetadata $metadata, array $args = []): BatchIteratorInterface;
}
