<?php
/**
 * Copyright 2024 Adobe
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

use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\DataExporter\Model\FeedExportStatus;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\DataExporter\Status\ExportStatusCodeProvider;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class responsible for feed data serialization
 * @link self::$mapping determinate the unique fields of feed's table their relationship to feed items from et_schema.
 * Support dot.notation
 * [
 *    "feed_table_column_name" => "field name in et_schema", // 'id' => 'product_id'
 *    "feed_table_column_name" => "parent_field_name.field_name"], // 'id' => ["product.id"]
 * ]
 *
 * @link self::$unserializeKeys allows to specify fields from et_schem that should be unserizlised before processing
 */
class DataSerializer implements DataSerializerInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var array
     */
    private $mapping;

    /**
     * @var array
     */
    private array $unserializeKeys;

    /**
     * @var CommerceDataExportLoggerInterface
     */
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @param SerializerInterface $serializer
     * @param CommerceDataExportLoggerInterface $logger
     * @param array $mapping
     * @param array $unserializeKeys
     */
    public function __construct(
        SerializerInterface $serializer,
        CommerceDataExportLoggerInterface $logger,
        array $mapping = [],
        array $unserializeKeys = [],
    ) {
        $this->serializer = $serializer;
        $this->mapping = $mapping;
        $this->unserializeKeys = $unserializeKeys;
        $this->logger = $logger;
    }

    /**
     * Serialize data
     *
     * @param array $data
     * @param ?FeedExportStatus $exportStatus
     * @param FeedIndexMetadata $metadata
     * @return array
     */
    public function serialize(array $data, ?FeedExportStatus $exportStatus, FeedIndexMetadata $metadata): array
    {
        if ($metadata->isExportImmediately()) {
            if ($exportStatus === null) {
                throw new \InvalidArgumentException('FeedExportStatus can\'t be null');
            }
            return $this->serializeForImmediateExport($data, $exportStatus, $metadata);
        }
        $output = [];

        foreach ($data as $feedData) {
            $outputRow = $this->buildRow($feedData);
            $outputRow[FeedIndexMetadata::FEED_TABLE_FIELD_FEED_DATA] = $this->serializer->serialize($feedData);

            $output[] = $outputRow;
        }
        return $output;
    }

    /**
     * Serialize for immediate export
     *
     * @param array $data
     * @param FeedExportStatus $exportStatus
     * @param FeedIndexMetadata $metadata
     * @return array
     */
    private function serializeForImmediateExport(
        array $data,
        FeedExportStatus $exportStatus,
        FeedIndexMetadata $metadata
    ): array {
        $output = [];
        $status = $exportStatus->getStatus();
        $exportFailedItems = $exportStatus->getFailedItems();
        $feedItemFields = array_values($metadata->getFeedItemIdentifiers());
        $feedItemFieldsToPersist = array_merge(
            $metadata->getMinimalPayloadFieldsList(),
            // required to build feed's table primary key if entity was deleted
            array_combine($feedItemFields, $feedItemFields),
        );
        $rowModifiedAt = (new \DateTime())->format($metadata->getDbDateTimeFormat());

        $itemN = -1;
        foreach ($data as $row) {
            $itemN++;
            $feedData = $row[FeedIndexMetadata::FEED_TABLE_FIELD_FEED_DATA];

            foreach ($this->unserializeKeys as $unserializeKey) {
                $feedData[$unserializeKey] = $this->serializer->unserialize($feedData[$unserializeKey]);
            }
            $sourceEntityId = $feedData[$metadata->getFeedIdentity()] ?? null;
            if ($sourceEntityId === null) {
                $this->logger->warning(
                    'Source entity id is null. Check your data. field: %s, Feed data: %s',
                    ['field' => $metadata->getFeedIdentity(), 'data' => var_export($feedData, true)]
                );
            }

            $outputRow = [FeedIndexMetadata::FEED_TABLE_FIELD_SOURCE_ENTITY_ID => $sourceEntityId];
            // get the first available value [feed.deleted, row.is_delete, 0]
            $outputRow[FeedIndexMetadata::FEED_TABLE_FIELD_IS_DELETED] = $feedData['deleted']
                ?? $row[FeedIndexMetadata::FEED_TABLE_FIELD_IS_DELETED]
                ?? 0;
            $outputRow[FeedIndexMetadata::FEED_TABLE_FIELD_STATUS] = $status->getValue();
            $outputRow[FeedIndexMetadata::FEED_TABLE_FIELD_MODIFIED_AT] = $rowModifiedAt;
            if (!empty($exportFailedItems)) {
                $failedFeedItem = $exportFailedItems[$itemN]['message'] ?? null;
                $outputRow[FeedIndexMetadata::FEED_TABLE_FIELD_ERRORS] = $failedFeedItem ?? '';
                // if _specific_ item failed mark only that item as failed, otherwise set status successful
                if ($failedFeedItem !== null) {
                    $outputRow[FeedIndexMetadata::FEED_TABLE_FIELD_STATUS]
                        = ExportStatusCodeProvider::FAILED_ITEM_ERROR;
                }
            } elseif (!$status->isSuccess()) {
                $outputRow[FeedIndexMetadata::FEED_TABLE_FIELD_ERRORS] = $exportStatus->getReasonPhrase();
            } else {
                $outputRow[FeedIndexMetadata::FEED_TABLE_FIELD_ERRORS] = '';
            }

            if (!$metadata->isPersistExportedFeed()) {
                // Save bare minimum of data: feed identity + items required to reconstruct primary keys of feed table
                // Used to cover "delete entity" use case to construct feed item with "deleted:true" field
                $feedData = \array_intersect_key($feedData, $feedItemFieldsToPersist);
            }
            $outputRow[FeedIndexMetadata::FEED_TABLE_FIELD_FEED_DATA] = $this->serializer->serialize($feedData);
            $outputRow[FeedIndexMetadata::FEED_TABLE_FIELD_FEED_HASH] = $row[
                FeedIndexMetadata::FEED_TABLE_FIELD_FEED_HASH
            ];
            if (IndexStateProvider::isUpdate($row)) {
                $outputRow[FeedIndexMetadata::FEED_TABLE_FIELD_PK] = $row[FeedIndexMetadata::FEED_TABLE_FIELD_PK];
                $output[IndexStateProvider::UPDATE_OPERATION][] = $outputRow;
            } else {
                $outputRow[FeedIndexMetadata::FEED_TABLE_FIELD_FEED_ID]
                    = $row[FeedIndexMetadata::FEED_TABLE_FIELD_FEED_ID];
                $output[IndexStateProvider::INSERT_OPERATION][] = $outputRow;
            }
        }
        return $output;
    }

    /**
     * Build row
     *
     * @param mixed $row
     * @return array
     */
    private function buildRow(mixed &$row): array
    {
        foreach ($this->unserializeKeys as $unserializeKey) {
            $row[$unserializeKey] = $this->serializer->unserialize($row[$unserializeKey]);
        }

        $outputRow = [];
        foreach ($this->mapping as $field => $index) {
            $value = $this->getNestedValue($row, $index);
            if (isset($value)) {
                $outputRow[$field] = is_array($value) ?
                    $this->serializer->serialize($value) :
                    $value;
            } else {
                $outputRow[$field] = null;
            }
        }
        return $outputRow;
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
            return (array_key_exists($key, $source)) ? $source[$key] : null;
        };
        return array_reduce($arrayPath, $reduce, $array);
    }
}
