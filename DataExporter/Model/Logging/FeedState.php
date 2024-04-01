<?php
/*************************************************************************
 *
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
 * ***********************************************************************
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
