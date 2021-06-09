<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

/**
 * Select Provider interface
 */
interface EntityIdsProviderInterface
{
    /**
     * Get all IDs
     *
     * @param FeedIndexMetadata $metadata
     * @return \Generator
     * @throws \Zend_Db_Statement_Exception
     */
    public function getAllIds(FeedIndexMetadata $metadata) : ?\Generator;
}
