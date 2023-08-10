<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\DataExporter\Model\Query\FeedQuery;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class responsible for providing feed data
 */
class Feed implements FeedInterface
{
    /**
     * @var ResourceConnection
     */
    protected ResourceConnection $resourceConnection;

    /**
     * @var SerializerInterface
     */
    protected SerializerInterface $serializer;

    /**
     * @var FeedIndexMetadata
     */
    protected FeedIndexMetadata $feedIndexMetadata;

    /**
     * @var FeedQuery
     */
    private FeedQuery $feedQuery;

    /**
     * @var string|null
     */
    private ?string $dateTimeFormat;

    /**
     * @var CommerceDataExportLoggerInterface
     */
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param SerializerInterface $serializer
     * @param FeedIndexMetadata $feedIndexMetadata
     * @param FeedQuery $feedQuery
     * @param CommerceDataExportLoggerInterface $logger
     * @param ?string $dateTimeFormat
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        SerializerInterface $serializer,
        FeedIndexMetadata $feedIndexMetadata,
        FeedQuery $feedQuery,
        CommerceDataExportLoggerInterface $logger,
        ?string $dateTimeFormat = null
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->serializer = $serializer;
        $this->feedIndexMetadata = $feedIndexMetadata;
        $this->feedQuery = $feedQuery;
        $this->logger = $logger;
        $this->dateTimeFormat = $dateTimeFormat;
    }

    /**
     * @inheritDoc
     */
    public function getFeedSince(string $timestamp, array $ignoredExportStatus = null): array
    {
        $connection = $this->resourceConnection->getConnection();
        $limit = $connection->fetchOne(
            $this->feedQuery->getLimitSelect(
                $this->feedIndexMetadata,
                $timestamp,
                $this->feedIndexMetadata->getFeedOffsetLimit(),
                $ignoredExportStatus
            )
        );
        return $this->fetchData(
            $this->feedQuery->getDataSelect(
                $this->feedIndexMetadata,
                $timestamp,
                !$limit ? null : $limit,
                $ignoredExportStatus
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
            if (null !== $this->dateTimeFormat) {
                try {
                    $dataRow['modifiedAt'] = (new \DateTime($dataRow['modifiedAt']))->format($this->dateTimeFormat);
                } catch (\Throwable $e) {
                    $this->logger->warning(\sprintf(
                        'Cannot convert modifiedAt "%s" to formant "%s", error: %s',
                        $dataRow['modifiedAt'],
                        $this->dateTimeFormat,
                        $e->getMessage()
                    ));
                }
            }
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

    /**
     * @inheirtDoc
     *
     * @return FeedIndexMetadata
     */
    public function getFeedMetadata(): FeedIndexMetadata
    {
        return $this->feedIndexMetadata;
    }
}
