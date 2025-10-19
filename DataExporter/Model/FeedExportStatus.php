<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model;

use Magento\DataExporter\Status\ExportStatusCode;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * DTO class to handle result of feed export
 */
class FeedExportStatus
{
    private ExportStatusCode $status;
    private string $reasonPhrase;
    private array $failedItems;
    private array $metadata;
    private Json $jsonSerializer;

    /**
     * @param ExportStatusCode $status
     * @param string $reasonPhrase
     * @param array $failedItems
     * @param Json $jsonSerializer
     * @param array $metadata
     */
    public function __construct(
        ExportStatusCode $status,
        string $reasonPhrase,
        array $failedItems,
        ?Json $jsonSerializer = null,
        ?array $metadata = []
    ) {
        $this->status = $status;
        $this->reasonPhrase = $reasonPhrase;
        $this->failedItems = $failedItems;
        $this->metadata = $metadata;
        $this->jsonSerializer = $jsonSerializer
            ?? ObjectManager::getInstance()->get(Json::class);
    }

    /**
     * Get reason phrase
     *
     * @return string
     */
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    /**
     * Get failed items
     *
     * @return array
     */
    public function getFailedItems(): array
    {
        return $this->failedItems;
    }

    /**
     * Get status
     *
     * @return ExportStatusCode
     */
    public function getStatus(): ExportStatusCode
    {
        return $this->status;
    }

    /**
     * Get metadata
     *
     * @return string
     */
    public function getMetadata(): string
    {
        return $this->jsonSerializer->serialize($this->metadata);
    }
}
