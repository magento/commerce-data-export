<?php
/**
 * Copyright 2021 Adobe
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
