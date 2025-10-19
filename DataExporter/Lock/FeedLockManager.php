<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
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
