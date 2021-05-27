<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogUrlRewriteDataExporter\Model\Provider\Category;

use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\CatalogUrlRewriteDataExporter\Model\Query\CategoryUrlQuery;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use \Magento\Catalog\Helper\Category;

/**
 * Category canonical url data provider
 */
class CanonicalUrl
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var CategoryUrlQuery
     */
    private $categoryUrlQuery;

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ResourceConnection $resourceConnection
     * @param CategoryUrlQuery $categoryUrlQuery
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        CategoryUrlQuery $categoryUrlQuery,
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->categoryUrlQuery = $categoryUrlQuery;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
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
                $queryArguments['categoryId'][$value['categoryId']] = $value['categoryId'];
                $queryArguments['storeViewCode'][$value['storeViewCode']] = $value['storeViewCode'];
            }

            foreach ($queryArguments['storeViewCode'] as $storeViewCode) {
                if ($this->useCanonicalTags($storeViewCode) === false) {
                    unset($queryArguments['storeViewCode'][$storeViewCode]);
                }
            }

            $select = $this->categoryUrlQuery->getQuery($queryArguments);
            $connection = $this->resourceConnection->getConnection();

            $cursor = $connection->query($select);
            while ($row = $cursor->fetch()) {
                $output[] = $row;
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve category canonical URL data');
        }

        return $output;
    }

    /**
     * Check if canonical url tag is being used for categories
     *
     * @param string $store
     * @return bool
     */
    public function useCanonicalTags(string $store) : bool
    {
        return (bool)$this->scopeConfig->getValue(
            Category::XML_PATH_USE_CATEGORY_CANONICAL_TAG,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}
