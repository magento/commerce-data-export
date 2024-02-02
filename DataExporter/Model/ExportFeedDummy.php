<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;

/**
 * Default implementation of ExportFeedInterface.
 *
 * Will throw an exception as it would be used only if SaaS Exporter will not be installed
 */
class ExportFeedDummy implements ExportFeedInterface
{
    /**
     * @inheritdoc
     * @param array $data
     * @param FeedIndexMetadata $metadata
     * @return FeedExportStatus
     */
    public function export(array $data, FeedIndexMetadata $metadata): FeedExportStatus
    {
        throw new \RuntimeException('\Magento\DataExporter\Model\ExportFeedInterface unimplemented');
    }
}
