<?php
/**
 * Copyright 2021 Adobe
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

namespace Magento\CatalogDataExporter\Model\Provider\Category\Formatter;

use Magento\DataExporter\Model\FailedItemsRegistry;
use Magento\Framework\App\ObjectManager;

/**
 * Provider data formatter
 */
class Formatter implements FormatterInterface
{
    /**
     * @var FormatterInterface[]
     */
    private array $formatters;
    private ?FailedItemsRegistry $failedItemsRegistry;

    /**
     * @param FormatterInterface[] $formatters
     * @param FailedItemsRegistry|null $failedRegistry
     */
    public function __construct(
        array $formatters = [],
        ?FailedItemsRegistry $failedRegistry = null
    ) {
        $this->formatters = $formatters;
        $this->failedItemsRegistry = $failedRegistry ?? ObjectManager::getInstance()->get(FailedItemsRegistry::class);
    }

    /**
     * @inheritDoc
     */
    public function format(array $row) : array
    {
        try {
            foreach ($this->formatters as $formatter) {
                $row = $formatter->format($row);
            }
        } catch (\Throwable $e) {
            $this->failedItemsRegistry->addFailed($row, $e);
        }

        return $row;
    }
}
