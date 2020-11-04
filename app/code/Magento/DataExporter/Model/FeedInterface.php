<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model;

/**
 * Interface FeedInterface
 */
interface FeedInterface
{
    /**
     * Get feed from given timestamp
     *
     * @param string $timestamp
     * @param null|string[] $storeViewCodes
     * @param array $attributes // entity_id => attributes_array relation
     *
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function getFeedSince(string $timestamp, ?array $storeViewCodes = [], array $attributes = []): array;

    /**
     * Get feed data by IDs
     *
     * @param int[] $ids
     * @param null|string[] $storeViewCodes
     * @param array $attributes // entity_id => attributes_array relation
     *
     * @return array
     */
    public function getFeedByIds(array $ids, ?array $storeViewCodes = [], array $attributes = []): array;

    /**
     * Get deleted entities by IDs
     *
     * @param string[] $ids
     * @param null|string[] $storeViewCodes
     * @return array
     */
    public function getDeletedByIds(array $ids, ?array $storeViewCodes = []): array;
}
