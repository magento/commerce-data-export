<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogUrlRewriteDataExporter\Model\Provider\Product;

use Magento\CatalogUrlRewriteDataExporter\Model\Query\ProductUrlRewritesQuery;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * UrlRewrites data provider
 */
class UrlRewrites
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductUrlRewritesQuery
     */
    private $urlRewritesQuery;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param StoreManagerInterface $storeManager
     * @param ProductUrlRewritesQuery $urlRewritesQuery
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        StoreManagerInterface $storeManager,
        ProductUrlRewritesQuery $urlRewritesQuery,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->urlRewritesQuery = $urlRewritesQuery;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * Format UrlRewrite data
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @param array $urlRewrite
     * @param string $storeViewCode
     * @return array
     */
    private function format(array $urlRewrite, string $storeViewCode) : array
    {
        $baseUrl = $this->storeManager->getStore($storeViewCode)->getBaseUrl(UrlInterface::URL_TYPE_WEB);

        return [
            'productId' => $urlRewrite[UrlRewrite::ENTITY_ID],
            'storeViewCode' => $storeViewCode,
            'urlRewrites' => [
                'url' => $baseUrl . $urlRewrite[UrlRewrite::REQUEST_PATH],
                'parameters' => $this->getUrlParameters($urlRewrite[UrlRewrite::TARGET_PATH])
            ]
        ];
    }

    /**
     * Get provider data
     *
     * @param array $values
     * @return array
     * @throws UnableRetrieveData
     */
    public function get(array $values): array
    {
        $output = [];
        $queryArguments = [];

        try {
            foreach ($values as $value) {
                $queryArguments['productId'][$value['productId']] = $value['productId'];
                $queryArguments['storeViewCode'][$value['storeViewCode']] = $value['storeViewCode'];
            }
            foreach ($queryArguments['storeViewCode'] as $storeViewCode) {
                $urlRewrites = $this->getUrlRewrites($queryArguments, $storeViewCode);
                foreach ($urlRewrites ?? [] as $urlRewrite) {
                    $output[] = $this->format($urlRewrite, $storeViewCode);
                }
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            throw new UnableRetrieveData('Unable to retrieve url rewrites data');
        }

        return $output;
    }

    /**
     * Get url rewrites for products and given store.
     *
     * @param array $queryArguments
     * @param string $storeViewCode
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getUrlRewrites(array $queryArguments, string $storeViewCode): array
    {
        $storeId = (int) $this->storeManager->getStore($storeViewCode)->getId();
        $urlRewritesSelect = $this->urlRewritesQuery->getQuery($queryArguments['productId'], $storeId);
        $connection = $this->resourceConnection->getConnection();

        return $connection->fetchAll($urlRewritesSelect);
    }

    /**
     * Parses target path and extracts parameters
     *
     * @param string $targetPath
     * @return array
     */
    private function getUrlParameters(string $targetPath): array
    {
        $urlParameters = [];
        $targetPathParts = explode('/', trim($targetPath, '/'));
        $targetPathPartsCount = count($targetPathParts);

        for ($i = 3; $i < $targetPathPartsCount - 1; $i += 2) {
            $urlParameters[] = [
                'name' => $targetPathParts[$i],
                'value' => $targetPathParts[$i + 1]
            ];
        }

        return $urlParameters;
    }
}
