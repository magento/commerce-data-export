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

namespace Magento\DataExporter\Lock;

use Magento\DataExporter\Model\Logging\FeedState;
use Magento\Framework\Lock\LockManagerInterface;

/**
 * Intended to prevent race conditions between different feed sync processes when the same feed using the same table.
 */
class FeedLockManager
{
    private const LOCK_PREFIX = 'feed_sync_';
    private const STATE_PROPERTY_NAME = 'lockedBy';

    private const DEFAULT_LOCK_TIME = 0;

    public function __construct(
        private readonly LockManagerInterface $lockManager,
        private readonly FeedState            $feedState
    ) {
    }

    /**
     * Set feed sync lock.
     *
     * @param string $feedName
     * @param string $lockedBy
     * @return bool
     */
    public function lock(string $feedName, string $lockedBy): bool
    {
        $isLocked = $this->lockManager->lock($this->getLockName($feedName), self::DEFAULT_LOCK_TIME);
        if ($isLocked) {
            try {
                $lockedBy = sprintf('%s(%s)', $lockedBy, getmypid());
                $this->feedState->save($feedName, self::STATE_PROPERTY_NAME, $lockedBy);
            } catch (\Throwable $ignore) {
                // ignore
            }
        }
        return $isLocked;
    }

    /**
     * @param string $feedName
     * @return string|null
     */
    public function getLockedByName(string $feedName): ?string
    {
        return $this->feedState->get($feedName, self::STATE_PROPERTY_NAME);
    }

    /**
     * Remove feed sync lock.
     *
     * @param string $feedName
     * @return bool
     */
    public function unlock(string $feedName): bool
    {
        return $this->lockManager->unlock($this->getLockName($feedName));
    }

    public function isLocked(string $feedName): bool
    {
        return $this->lockManager->isLocked($this->getLockName($feedName));
    }

    /**
     * @param $feedName
     * @return string
     */
    private function getLockName($feedName): string
    {
        return self::LOCK_PREFIX . $feedName;
    }
}
