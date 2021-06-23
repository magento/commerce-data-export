<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ProductReviewDataExporter\Model\Indexer;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\DataExporter\Model\Indexer\DataSerializerInterface;

/**
 * Class responsible for rating feed data serialization
 */
class RatingDataSerializer implements DataSerializerInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var string
     */
    private $feedIdentity;

    /**
     * @var array
     */
    private $mapping;

    /**
     * @param SerializerInterface $serializer
     * @param string $feedIdentity
     * @param array $mapping
     */
    public function __construct(
        SerializerInterface $serializer,
        string $feedIdentity,
        array $mapping = []
    ) {
        $this->serializer = $serializer;
        $this->feedIdentity = $feedIdentity;
        $this->mapping = $mapping;
    }

    /**
     * Serialize feed data
     *
     * @param array $data
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function serialize(array $data): array
    {
        $output = [];
        foreach ($data as $row) {
            $outputRow = [];

            foreach ($this->mapping as $field => $index) {
                $outputRow[$field] = isset($row[$index]) ? $row[$index] : null;
            }

            // Keep feed table "id" decoded and encode "feed data identity"
            $row[$this->feedIdentity] = \base64_encode($row[$this->feedIdentity]);
            $outputRow['feed_data'] = $this->serializer->serialize($row);
            $output[] = $outputRow;
        }

        return $output;
    }
}
