<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product\Formatter;

/**
 * Provider data formatter
 */
class Formatter implements FormatterInterface
{
    /**
     * @var FormatterInterface[]
     */
    private $formatters;

    /**
     * Formatter constructor.
     *
     * @param FormatterInterface[] $formatters
     */
    public function __construct(
        array $formatters = []
    ) {
        $this->formatters = $formatters;
    }

    /**
     * @inheritDoc
     */
    public function format(array $row): array
    {
        foreach ($this->formatters as $formatter) {
            $row = $formatter->format($row);
        }
        return $row;
    }
}
