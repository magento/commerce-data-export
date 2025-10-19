<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

/**
 * Feed indexer config options pool.
 */
class ConfigOptionsPool
{
    /**
     * @var array
     */
    private array $feedOptions;

    /**
     * @param array $feedOptions
     */
    public function __construct(array $feedOptions)
    {
        $this->feedOptions = $feedOptions;
    }

    /**
     * Get feed option value by its name
     *
     * @param string $optionName
     * @return mixed
     */
    public function getFeedOption(string $optionName): mixed
    {
        return $this->feedOptions[$optionName] ?? null;
    }
}
