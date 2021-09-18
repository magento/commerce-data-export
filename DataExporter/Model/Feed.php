<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Model\Query\FeedQuery;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class responsible for providing feed data
 */
class Feed implements FeedInterface
{
    /**
     * Offset
     *
     * @var int
     */
    protected const OFFSET = 100;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var FeedIndexMetadata
     */
    protected $feedIndexMetadata;

    /**
     * @var FeedQuery
     */
    private $feedQuery;

    /**
     * @param ResourceConnection $resourceConnection
     * @param SerializerInterface $serializer
     * @param FeedIndexMetadata $feedIndexMetadata
     * @param FeedQuery $feedQuery
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        SerializerInterface $serializer,
        FeedIndexMetadata $feedIndexMetadata,
        FeedQuery $feedQuery
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->serializer = $serializer;
        $this->feedIndexMetadata = $feedIndexMetadata;
        $this->feedQuery = $feedQuery;
    }

    /**
     * @inheritDoc
     */
    public function getFeedSince(string $timestamp): array
    {
        $connection = $this->resourceConnection->getConnection();
        $limit = $connection->fetchOne(
            $this->feedQuery->getLimitSelect(
                $this->feedIndexMetadata,
                $timestamp,
                $this::OFFSET
            )
        );
        return $this->fetchData(
            $this->feedQuery->getDataSelect(
                $this->feedIndexMetadata,
                $timestamp,
                !$limit ? null : $limit
            )
        );
    }

    /**
     * Fetch data from prepared select statement
     *
     * @param string|\Magento\Framework\DB\Select $select
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    private function fetchData($select): array
    {
        $connection = $this->resourceConnection->getConnection();
        $recentTimestamp = null;

        $cursor = $connection->query($select);
        $output = [];
        while ($row = $cursor->fetch()) {
            $dataRow = $this->serializer->unserialize($row['feed_data']);
            $dataRow['modifiedAt'] = $row['modifiedAt'];
            if (isset($row['deleted'])) {
                $dataRow['deleted'] = (bool)$row['deleted'];
            }
            $output[] = $dataRow;
            if ($recentTimestamp === null || $recentTimestamp < $row['modifiedAt']) {
                $recentTimestamp = $row['modifiedAt'];
            }
        }
        return [
            'recentTimestamp' => $recentTimestamp,
            'feed' => $output,
        ];
    }
}
