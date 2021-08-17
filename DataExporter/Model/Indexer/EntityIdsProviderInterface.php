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

    /**
     * Returns all affected IDs
     * suppose to resolve parent to child relationship
     * returns input array if no relation defined
     *
     * @param FeedIndexMetadata $metadata
     * @param array $ids
     * @return array
     */
    public function getAffectedIds(FeedIndexMetadata $metadata, array $ids): array;
}
