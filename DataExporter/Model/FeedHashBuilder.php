<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Build hash for feed record
 */
class FeedHashBuilder
{
    private const FEED_ID_SEPARATOR = "\0";
    private SerializerInterface $serializer;
    private ResourceConnection $resourceConnection;
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @param SerializerInterface $serializer
     * @param ResourceConnection $resourceConnection
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        SerializerInterface $serializer,
        ResourceConnection $resourceConnection,
        CommerceDataExportLoggerInterface $logger
    ) {
        $this->serializer = $serializer;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * Hash row data
     *
     * @param array $row
     * @param FeedIndexMetadata $metadata
     * @return string
     */
    public function buildHash(array $row, FeedIndexMetadata $metadata) : string
    {
        return sha1($this->serializer->serialize($this->sanitizeRow($row, $metadata)));
    }

    /**
     * Sanitize row
     *
     * @param array $row
     * @param FeedIndexMetadata $metadata
     * @return array
     */
    private function sanitizeRow(array $row, FeedIndexMetadata $metadata): array
    {
        foreach (array_keys($row) as $key) {
            if (\in_array($key, $metadata->getExcludeFromHashFields(), true)) {
                unset($row[$key]);
            }
        }
        return $row;
    }

    /**
     * Build identifier from feed item
     *
     * @param array $feedItem
     * @param FeedIndexMetadata $metadata
     * @return string
     */
    public function buildIdentifierFromFeedItem(array $feedItem, FeedIndexMetadata $metadata): string
    {
        $identifier = [];
        foreach ($metadata->getFeedItemIdentifiers() as $field) {
            $value = $this->getNestedValue($feedItem, $field);
            if ($value === null) {
                $this->logger->error(
                    "Cannot build identifier for '$field' from feed item: " . var_export($feedItem, true)
                );
                continue;
            }
            $identifier[] = $value;
        }

        return sha1(implode(self::FEED_ID_SEPARATOR, $identifier));
    }

    /**
     * Get nested array value.
     *
     * @param array $array
     * @param string $path
     * @return mixed
     */
    private function getNestedValue(array $array, string $path): mixed
    {
        $arrayPath = explode('.', $path);
        $reduce = function (array $source, $key) {
            return array_key_exists($key, $source) ? $source[$key] : null;
        };
        return array_reduce($arrayPath, $reduce, $array);
    }
}
