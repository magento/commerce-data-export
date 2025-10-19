<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Logging;

use Magento\Framework\FlagManager;

/**
 * Save feed state in DB
 */
class FeedState
{
    private const FLAG_PREFIX = 'feed_metadata_';

    public function __construct(
        private readonly FlagManager $flagManager
    ) {
    }

    /**
     * @param string $feedName
     * @param string $name
     * @param string $value
     * @return void
     */
    public function save(string $feedName, string $name, string $value): void
    {
        $this->flagManager->saveFlag($this->getFlagName($feedName), [$name => $value]);
    }

    /**
     * @param string $feedName
     * @param string $name
     * @return string|null
     */
    public function get(string $feedName, string $name): ?string
    {
        $value = $this->flagManager->getFlagData($this->getFlagName($feedName));
        return $value !== null ? $value[$name] ?? null : null;
    }

    /**
     * @param $feedName
     * @return string
     */
    private function getFlagName($feedName): string
    {
        return self::FLAG_PREFIX . $feedName;
    }
}
