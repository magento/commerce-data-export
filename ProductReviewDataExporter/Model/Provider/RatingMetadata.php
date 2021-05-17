<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductReviewDataExporter\Model\Provider;

use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\ProductReviewDataExporter\Model\Query\RatingMetadataQuery;
use Psr\Log\LoggerInterface;

/**
 * Rating metadata data provider
 */
class RatingMetadata
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var RatingMetadataQuery
     */
    private $ratingMetadataQuery;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param RatingMetadataQuery $ratingMetadataQuery
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        RatingMetadataQuery $ratingMetadataQuery,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->ratingMetadataQuery = $ratingMetadataQuery;
        $this->logger = $logger;
    }

    /**
     * Returns attribute data
     *
     * @param array $values
     *
     * @return array
     *
     * @throws UnableRetrieveData
     */
    public function get(array $values): array
    {
        $output = [];
        $queryArguments = [];

        try {
            foreach ($values as $value) {
                $queryArguments[$value['ratingId']] = $value['ratingId'];
            }

            $connection = $this->resourceConnection->getConnection();
            $cursor = $connection->query($this->ratingMetadataQuery->getQuery($queryArguments));

            while ($row = $cursor->fetch()) {
                $key = $row['ratingId'] . $row['storeViewCode'];
                $output[$key] = $output[$key] ?? $this->formatRatingRow($row);
                $output[$key]['values'][] = [
                    'valueId' => \base64_encode($row['valueId']),
                    'value' => $row['value'],
                    'position' => $row['position'],
                ];
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve rating metadata data');
        }

        return \array_values($output);
    }

    /**
     * Format rating row
     *
     * @param array $row
     *
     * @return array
     */
    private function formatRatingRow(array $row): array
    {
        return [
            'ratingId' => $row['ratingId'],
            'storeViewCode' => $row['storeViewCode'],
            'name' => $row['name'],
        ];
    }
}
