<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
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
