<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;

/**
 * Pool of all existing FeedIndexMetadata
 */
class FeedMetadataPool
{
    /**
     * @var array
     */
    private $classMap;

    /**
     * @param array $classMap
     */
    public function __construct(
        array $classMap = []
    ) {
        $this->classMap = array_filter($classMap, fn($feed) => $feed instanceof FeedIndexMetadata);
    }

    /**
     * Returns feed object
     *
     * @param string $feedName
     * @return FeedIndexMetadata
     * @throws \InvalidArgumentException
     */
    public function getMetadata(string $feedName) : FeedIndexMetadata
    {
        if (!isset($this->classMap[$feedName])) {
            throw new \InvalidArgumentException(
                \sprintf('Not registered FeedIndexMetadata for feed "%s"', $feedName)
            );
        }
        return $this->classMap[$feedName];
    }

    /**
     * Get list of all registered feeds
     *
     * @return array
     */
    public function getAll(): array
    {
        return $this->classMap;
    }
}
