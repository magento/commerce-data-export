<?php
/**
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2025 Adobe
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

namespace Magento\DataExporterStatus\Service\DTO;

use Magento\Framework\Indexer\IndexerInterface;

class IndexerStatus
{
    public function __construct(
        private readonly IndexerInterface $indexer,
        private readonly int $changelogBacklog,
        private readonly array $changelogIds,
        private readonly ?string $changelogLastUpdated,
    ) {}

    public function getChangelogBacklog(): int
    {
        return $this->changelogBacklog;
    }

    public function getChangelogIds(): array
    {
        return $this->changelogIds;
    }

    public function getIndexer(): IndexerInterface
    {
        return $this->indexer;
    }

    public function getChangelogLastUpdated(): ?string
    {
        return $this->changelogLastUpdated;
    }
}