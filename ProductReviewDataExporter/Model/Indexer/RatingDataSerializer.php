<?php
/**
 * Copyright 2023 Adobe
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

namespace Magento\ProductReviewDataExporter\Model\Indexer;

use Magento\DataExporter\Model\FeedExportStatus;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
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
     * @param ?FeedExportStatus $exportStatus
     * @param FeedIndexMetadata $metadata
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function serialize(array $data, ?FeedExportStatus $exportStatus, FeedIndexMetadata $metadata): array
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
