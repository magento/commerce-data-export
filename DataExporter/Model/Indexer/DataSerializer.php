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
                if (isset($row[$index])) {
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
