<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

use Magento\DataExporter\Model\FeedExportStatus;

/**
 * Feed data serializer interface
 */
interface DataSerializerInterface
{
    /**
     * Serialize data
     *
     * @param array $data
     * @param ?FeedExportStatus $exportStatus
     * @param FeedIndexMetadata $metadata
     * @return array
     */
    public function serialize(array $data, ?FeedExportStatus $exportStatus, FeedIndexMetadata $metadata): array;
}
