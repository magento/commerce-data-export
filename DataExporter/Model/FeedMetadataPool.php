<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
        $this->classMap = $classMap;
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
