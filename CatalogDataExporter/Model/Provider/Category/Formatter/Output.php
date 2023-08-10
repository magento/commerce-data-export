<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Category\Formatter;

use Magento\Catalog\Helper\Output as OutputFormatter;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;
use Magento\Framework\App\Area as AppArea;
use Magento\Framework\App\State as AppState;

/**
 * Prepare category attribute html output
 */
class Output implements FormatterInterface
{

    /**
     * @var AppState
     */
    private $appState;

    /**
     * @var OutputFormatter
     */
    private $outputFormatter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string[]
     */
    private $attributes;

    /**
     * @param AppState $appState
     * @param OutputFormatter $outputFormatter
     * @param LoggerInterface $logger
     * @param array $attributes
     */
    public function __construct(
        AppState $appState,
        OutputFormatter $outputFormatter,
        LoggerInterface $logger,
        array $attributes = []
    ) {
        $this->appState = $appState;
        $this->outputFormatter = $outputFormatter;
        $this->logger = $logger;
        $this->attributes = $attributes;
    }

    /**
     * Format provider data
     *
     * @param array $row
     *
     * @return array
     *
     * @throws UnableRetrieveData
     */
    public function format(array $row) : array
    {
        try {
            foreach ($this->attributes as $attributeKey => $attributeCode) {
                if (isset($row[$attributeKey])) {
                    $outputFormatter = $this->outputFormatter;
                    $row[$attributeKey] = $this->appState->emulateAreaCode(
                        AppArea::AREA_FRONTEND,
                        function () use ($outputFormatter, $row, $attributeCode, $attributeKey) {
                            return $outputFormatter->categoryAttribute(
                                null,
                                $row[$attributeKey],
                                $attributeCode
                            );
                        }
                    );
                }
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            throw new UnableRetrieveData('Unable to retrieve category formatted attribute data');
        }

        return $row;
    }
}
