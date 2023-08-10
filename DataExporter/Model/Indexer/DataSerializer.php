<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

use Magento\DataExporter\Model\FeedExportStatus;
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
     * @param SerializerInterface $serializer
     * @param array $mapping
     * @param array $unserializeKeys
     */
    public function __construct(
        SerializerInterface $serializer,
        array $mapping = [],
        array $unserializeKeys = [],
    ) {
        $this->serializer = $serializer;
        $this->mapping = $mapping;
        $this->unserializeKeys = $unserializeKeys;
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
        $feedItemFields = array_values($metadata->getFeedIdentifierMappingFields());
        $feedItemFieldsToPersist = array_merge(
            $metadata->getMinimalPayloadFieldsList(),
            // required to build feed's table primary key if entity was deleted
            array_combine($feedItemFields, $feedItemFields),
        );

        $itemN = -1;
        foreach ($data as $row) {
            $itemN++;
            $feedData = $row['feed'];

            foreach ($this->unserializeKeys as $unserializeKey) {
                $feedData[$unserializeKey] = $this->serializer->unserialize($feedData[$unserializeKey]);
            }
            $outputRow = $this->buildRow($feedData);

            // get the first available value [feed.deleted, row.delete, 0]
            $outputRow[FeedIndexMetadata::FEED_TABLE_FIELD_IS_DELETED] = $feedData['deleted'] ?? $row['deleted'] ?? 0;
            $outputRow[FeedIndexMetadata::FEED_TABLE_FIELD_STATUS] = $status->getValue();
            $outputRow[FeedIndexMetadata::FEED_TABLE_FIELD_MODIFIED_AT] = $feedData['modifiedAt'];

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
            $outputRow[FeedIndexMetadata::FEED_TABLE_FIELD_FEED_HASH] = $row['hash'];

            $output[] = $outputRow;
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
