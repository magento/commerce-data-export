<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Model;

use Magento\DataExporter\Model\Feed;

/**
 * Class responsible for providing product variant feed data
 */
class ProductVariantFeed extends Feed implements ProductVariantFeedInterface
{
    /**
     * @inheritDoc
     */
    public function getFeedByProductIds(array $entityIds): array
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from(
                ['t' => $this->resourceConnection->getTableName($this->feedIndexMetadata->getFeedTableName())],
                [
                    'feed_data',
                    'modified_at',
                    'is_deleted'
                ]
            )
            ->where('t.is_deleted = ?', 0)
            ->where(sprintf('t.%s IN (?)', $this->feedIndexMetadata->getFeedTableParentField()), $entityIds);

        return $this->fetchData($select, []);
    }

    /**
     * @inheritDoc
     */
    public function getDeletedByProductIds(array $entityIds): array
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from(
                ['t' => $this->resourceConnection->getTableName($this->feedIndexMetadata->getFeedTableName())],
                [
                    'feed_data',
                ]
            )
            ->where('t.is_deleted = ?', 1)
            ->where(sprintf('t.%s IN (?)', $this->feedIndexMetadata->getFeedTableParentField()), $entityIds);

        $connection = $this->resourceConnection->getConnection();
        $cursor = $connection->query($select);

        $output = [];
        while ($row = $cursor->fetch()) {
            $output[] = $this->serializer->unserialize($row['feed_data']);
        }

        return $output;
    }
}
