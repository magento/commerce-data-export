<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
