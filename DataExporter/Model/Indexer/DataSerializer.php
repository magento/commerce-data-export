<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class responsible for feed data serialization
 * "mapping" field determinate the unique field for feed table based on data from et_schema. Support both single and multi dimension values. Example format:
 * [
 *    "feed_table_column_name" => "field name in et_schema", // 'id' => 'product_id'
 *    "feed_table_column_name" => ["complex field type", "in et_schema"], // 'id' => ["product", "id"]
 * ]
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
     * @param SerializerInterface $serializer
     * @param array $mapping
     */
    public function __construct(
        SerializerInterface $serializer,
        array $mapping = []
    ) {
        $this->serializer = $serializer;
        $this->mapping = $mapping;
    }

    /**
     * Serialize data
     *
     * @param array $data
     * @return array
     */
    public function serialize(array $data): array
    {
        $output = [];
        foreach ($data as $row) {
            $outputRow = [];
            $outputRow['feed_data'] = $this->serializer->serialize($row);
            foreach ($this->mapping as $field => $index) {
                if (\is_array($index)) {
                    $indexValue = null;
                    foreach ($index as $key) {
                        $indexValue = $indexValue
                            ? $indexValue[$key] ?? null
                            : $row[$key] ?? null;
                    }
                    $outputRow[$field] = $indexValue;
                } elseif (isset($row[$index])) {
                    $outputRow[$field] = is_array($row[$index]) ?
                        $this->serializer->serialize($row[$index]) :
                        $row[$index];
                } else {
                    $outputRow[$field] = null;
                }
            }
            $output[] = $outputRow;
        }
        return $output;
    }
}
