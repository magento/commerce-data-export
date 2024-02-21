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
