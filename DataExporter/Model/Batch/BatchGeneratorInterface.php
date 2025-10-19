<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
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
