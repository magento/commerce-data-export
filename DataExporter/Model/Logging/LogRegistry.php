<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Logging;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;

class LogRegistry
{
    public const PROCESSED_ITEMS = 'PROCESSED_ITEMS';
    public const SYNCED_ITEMS = 'SYNCED_ITEMS';
    public const COMPLETE_ITERATION = 'COMPLETE_ITERATION';
    public const TOTAL_ITERATIONS = 'iterations';

    private const UNDEFINED = 'undefined';
    private string $operation;
    private string $feedName;
    private array $startTime;
    private int $threadsCount = 1;
    private int $mainThread;
    private bool $logInitialized = false;
    private int $processed;
    private int $synced;
    private int $logProgressIntervalChunks;
    private ?int $userDefinedLogProgressInterval;
    private int $currentIteration;
    private ?int $totalIterationsPerThread;

    public function __construct($logProgressInterval = null)
    {
        $this->userDefinedLogProgressInterval = $logProgressInterval !== null ? (int)$logProgressInterval : null;
    }

    public function initSyncLog(FeedIndexMetadata $metadata, string $operation): void
    {
        $this->feedName = $metadata->getFeedName();
        $this->threadsCount = $metadata->getThreadCount();
        $this->operation = $operation;
        $this->init();
    }

    /**
     * @param array $context
     * @return void
     */
    public function addContext(array $context): void
    {
        if (isset($context[self::TOTAL_ITERATIONS])) {
            $this->totalIterationsPerThread = (int)ceil($context[self::TOTAL_ITERATIONS] / $this->threadsCount);
        }
    }

    /**
     * @return void
     */
    private function init(): void
    {
        $this->mainThread = (int)getmypid();
        $this->startTime = hrtime(false);
        $this->logInitialized = true;
        $this->processed = 0;
        $this->synced = 0;
        $this->logProgressIntervalChunks = 0;
        $this->totalIterationsPerThread = null;
        $this->currentIteration = 0;
    }
    /**
     * @return string
     */
    private function getOperation(): string
    {
        return $this->operation ?? self::UNDEFINED;
    }

    /**
     * @return string
     */
    private function getFeedName(): string
    {
        return $this->feedName ?? self::UNDEFINED;
    }

    /**
     * @return string
     */
    private function getElapsedTime(): string
    {
        return $this->formatTime($this->startTime, hrtime(false));
    }

    /**
     * @return int
     */
    private function getElapsedSeconds(): int
    {
        return (int)(hrtime(false)[0] - $this->startTime[0]);
    }

    /**
     * @return string|null
     */
    private function getThreadId(): string|null
    {
        $pid = $this->getPid();
        if ($this->threadsCount > 1) {
            return $pid === $this->mainThread ? "main($pid)" : "main($this->mainThread)::child($pid)";
        }
        return (string)$pid;
    }

    /**
     * @return int
     */
    private function getPid(): int
    {
        return (int)getmypid();
    }

    /**
     * @param $message
     * @param array $context
     * @return string
     */
    public function prepareMessage($message, array $context = []): string
    {
        if (!$this->logInitialized) {
            // TODO: log trace for troubleshooting
            // make the best assumption in case log was not initialized
            $this->init();
        }
        $logProcessStatus = false;
        if (isset($context[self::PROCESSED_ITEMS])) {
            $this->processed += $context[self::PROCESSED_ITEMS];
            $logProcessStatus = true;
        }
        if (isset($context[self::SYNCED_ITEMS])) {
            $this->synced += $context[self::SYNCED_ITEMS];
            $logProcessStatus = true;
        }

        if (isset($context[self::COMPLETE_ITERATION])) {
            $this->currentIteration++;
            if ($this->isFinalIteration()) {
                // to cover edge case
                $logProcessStatus = true;
            } else {
                return '';
            }
        }

        if ($logProcessStatus) {
            if ($this->isTimeToLogProgress()) {
                $progress = $this->isLogTotalProgress()
                    ? sprintf('Progress %s/%s, ', $this->currentIteration, $this->totalIterationsPerThread)
                    : '';
                $message = sprintf('%sprocessed %s, synced %s', $progress, $this->processed, $this->synced);
            } else {
                // empty message
                return '';
            }
        }

        $message = [
            'feed' => $this->getFeedName(),
            'operation' => $this->getOperation(),
            'status' => $message,
        ];

        if (!empty($this->startTime)) {
            $message['elapsed'] = $this->getElapsedTime();
        }

        $message['pid'] = $this->getThreadId();
        $message['caller'] = $this->getCallerInfo();

        return json_encode($message);
    }

    /**
     * Determine how frequently log message
     *
     * @return bool
     */
    private function isTimeToLogProgress(): bool
    {
        if ($this->isFinalIteration()) {
            return true;
        }
        $elapsedTime = $this->getElapsedSeconds();
        $chunks = intdiv($elapsedTime, $this->getLogProgressTimeInterval());
        $chunks -= $this->logProgressIntervalChunks;
        if ($chunks > 0) {
            $this->logProgressIntervalChunks += $chunks;
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    private function isFinalIteration(): bool
    {
        return $this->isLogTotalProgress() && $this->currentIteration == $this->totalIterationsPerThread;
    }

    /**
     * How frequently log sync status. Return value in seconds
     * @return int
     */
    private function getLogProgressTimeInterval(): int
    {
        if ($this->userDefinedLogProgressInterval !== null && $this->userDefinedLogProgressInterval > 0) {
            return $this->userDefinedLogProgressInterval;
        }
        $defaultInterval = 30;
        if ($this->totalIterationsPerThread === null) {
            return $defaultInterval;
        }
        return match (true) {
            $this->totalIterationsPerThread < 1000 => 60, // 1 min
            $this->totalIterationsPerThread < 10000 => 120, // 22 2 min
            default => 600, // 10 min
        };
    }

    /**
     * @return string|null
     */
    private function getCallerInfo(): ?string
    {
        $cli = $_SERVER['argv'] ?? [];
        if ($cli) {
            return \implode(' ', \array_slice($cli, 0, 6));
        } else {
            return 'app';
        }
    }

    /**
     * @return bool
     */
    private function isLogTotalProgress(): bool
    {
        return $this->totalIterationsPerThread !== null && $this->currentIteration > 0;
    }
    /**
     * Format time to display in days, hours, minutes and seconds, ms
     * @param array $before
     * @param array $after
     * @return string
     */
    private function formatTime(array $before, array $after): string
    {
        $seconds = $after[0] - $before[0];
        $secondsInDay = 86400;
        $daysCount = intdiv($seconds, $secondsInDay);
        $seconds -= $secondsInDay*$daysCount;
        $time = gmdate('H:i:s', $seconds);
        if ($daysCount > 0) {
            return sprintf('%s day(s) %s', $daysCount, $time);
        } else {
            $ms = round(($after[1] + $before[1])/1e+6);
            $addedSeconds = intdiv((int)$ms, 1000);
            $seconds += $addedSeconds;
            $ms -= $addedSeconds * 1000;
            return $seconds > 0 ? $time  . " $ms ms" : "$ms ms";
        }
    }
}
