<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Category;

use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * Category available sort by data provider
 */
class AvailableSortBy
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get provider data
     *
     * @param array $values
     *
     * @return array
     *
     * @throws UnableRetrieveData
     */
    public function get(array $values) : array
    {
        try {
            $output = [];

            foreach ($values as $value) {
                if (empty($value['availableSortBy'])) {
                    continue;
                }

                foreach (\explode(',', $value['availableSortBy']) as $sortByOption) {
                    $output[] = [
                        'categoryId' => $value['categoryId'],
                        'storeViewCode' => $value['storeViewCode'],
                        'availableSortBy' => $sortByOption,
                    ];
                }
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            throw new UnableRetrieveData('Unable to retrieve category available sort by data');
        }

        return $output;
    }
}
