<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Category;

use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Category store path data provider
 */
class PathInStore
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(LoggerInterface $logger, StoreManagerInterface $storeManager)
    {
        $this->logger = $logger;
        $this->storeManager = $storeManager;
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
            $queryArguments = [];
            $output = [];

            foreach ($values as $value) {
                $queryArguments['storeViewCode'][$value['storeViewCode']] = $value['storeViewCode'];
            }

            $rootCategoriesIds = $this->provideRootCategoriesIdsByStoreViewCodes($queryArguments['storeViewCode']);

            foreach ($values as $value) {
                $pathInStore = [];

                foreach (\array_reverse(\explode('/', $value['path'])) as $pathId) {
                    if ($pathId === $rootCategoriesIds[$value['storeViewCode']]) {
                        break;
                    }

                    $pathInStore[] = $pathId;
                }

                $output[] = [
                    'categoryId' => $value['categoryId'],
                    'storeViewCode' => $value['storeViewCode'],
                    'pathInStore' => \implode(',', $pathInStore),
                ];
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve category store path data');
        }

        return $output;
    }

    /**
     * Provide root categories ids by store view codes
     *
     * @param array $storeViewCodes
     *
     * @return array
     */
    private function provideRootCategoriesIdsByStoreViewCodes(array $storeViewCodes) : array
    {
        $categories = [];

        foreach ($storeViewCodes as $storeViewCode) {
            $categories[$storeViewCode] = $this->storeManager->getStore($storeViewCode)->getRootCategoryId();
        }

        return $categories;
    }
}
