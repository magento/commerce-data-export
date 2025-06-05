<?php
/**
 * Copyright 2022 Adobe
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

namespace Magento\CatalogDataExporter\Model\Provider\Product\ProductOptions;

use Exception;
use InvalidArgumentException;
use Magento\CatalogDataExporter\Model\Provider\Product\Downloadable\SampleUrlProvider;
use Magento\CatalogDataExporter\Model\Query\ProductDownloadableLinksQuery;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\StoreRepositoryInterface;

/**
 * Product downloadable links data provider
 */
class DownloadableLinks
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ProductDownloadableLinksQuery
     */
    private $productDownloadableLinksQuery;

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @var DownloadableLinksOptionUid
     */
    private $downloadableLinksOptionUid;

    /**
     * @var SampleUrlProvider
     */
    private $sampleUrlProvider;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ProductDownloadableLinksQuery $productDownloadableLinksQuery
     * @param StoreRepositoryInterface $storeRepository
     * @param DownloadableLinksOptionUid $downloadableLinksOptionUid
     * @param SampleUrlProvider $sampleUrlProvider
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ProductDownloadableLinksQuery $productDownloadableLinksQuery,
        StoreRepositoryInterface $storeRepository,
        DownloadableLinksOptionUid $downloadableLinksOptionUid,
        SampleUrlProvider $sampleUrlProvider
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->productDownloadableLinksQuery = $productDownloadableLinksQuery;
        $this->storeRepository = $storeRepository;
        $this->downloadableLinksOptionUid = $downloadableLinksOptionUid;
        $this->sampleUrlProvider = $sampleUrlProvider;
    }

    /**
     * Get downloadable links with link values
     *
     * @param array $values
     * @return array
     * @throws Exception
     */
    public function get(array $values): array
    {
        $productIds = [];
        $storeViews = [];
        $output = [];
        foreach ($values as $value) {
            if ($value['type'] === DownloadableLinksOptionUid::OPTION_TYPE) {
                $productIds[] = $value['productId'];
                if (!isset($storeViews[$value['storeViewCode']])) {
                    $storeViews[$value['storeViewCode']] = (int)$this->storeRepository->get($value['storeViewCode'])
                        ->getId();
                }
            }
        }
        if (!empty($productIds)) {
            $productIds = array_unique($productIds);
            foreach ($storeViews as $storeViewCode => $storeId) {
                $downloadableLinksSelect = $this->productDownloadableLinksQuery->getQuery($productIds, $storeId);
                $downloadableLinksQuery = $this->resourceConnection->getConnection()->query($downloadableLinksSelect);
                $linkOptions = $downloadableLinksQuery->fetchAll();
                $output += $this->format(
                    $linkOptions,
                    $this->buildProductAttributes($values, $productIds),
                    $storeViewCode
                );
            }
        }
        return $output;
    }

    /**
     * Format provider data
     *
     * @param array $linkOptions
     * @param array $attributes
     * @param string $storeViewCode
     *
     * @return array
     *
     * @throws NoSuchEntityException
     */
    private function format(array $linkOptions, array $attributes, string $storeViewCode): array
    {
        $output = [];
        $products = array_keys($attributes);
        foreach ($products as $productId) {
            $key = (string)$productId . $storeViewCode;
            $output[$key] = [
                'productId' => (string)$productId,
                'storeViewCode' => $storeViewCode,
                'optionsV2' => [
                    'id' => 'link:' . (string)$productId,
                    'label' => $attributes[(string)$productId]['links_title'],
                    'type' => DownloadableLinksOptionUid::OPTION_TYPE,
                    'values' => $this->processOptionValues($linkOptions, (string)$productId, $storeViewCode)
                ]
            ];
        }
        return $output;
    }

    /**
     * Process option values.
     *
     * @param array $linkOptions
     * @param string $productId
     * @param string $storeViewCode
     *
     * @return array
     *
     * @throws NoSuchEntityException
     */
    private function processOptionValues(array $linkOptions, string $productId, string $storeViewCode): array
    {
        $values = [];
        foreach ($linkOptions as $option) {
            if ($productId == $option['entity_id']) {
                $values[] = [
                    'id' => $this->downloadableLinksOptionUid->resolve(
                        [
                            DownloadableLinksOptionUid::OPTION_ID => $option['link_id'],
                        ]
                    ),
                    'label' => $option['title'],
                    'sortOrder' => $option['sort_order'],
                    'infoUrl' => $this->getLinkSampleUrl($option, $storeViewCode),
                    'price' => (float)$option['price'],
                    'qty' => $option['number_of_downloads'] ?? 0
                ];
            }
        }
        return $values;
    }

    /**
     * Retrieve link sample url
     *
     * @param array $option
     * @param string $storeViewCode
     *
     * @return string|null
     *
     * @throws NoSuchEntityException
     */
    private function getLinkSampleUrl(array $option, string $storeViewCode): ?string
    {
        if (null !== $option['sample_url']) {
            return $this->sampleUrlProvider->getBaseSampleUrlByStoreViewCode($storeViewCode) . $option['link_id'];
        }

        return null;
    }

    /**
     * Build the downloadable product attributes
     *
     * @param array $products
     * @param array $downloadableProductIds
     * @return array
     */
    private function buildProductAttributes(array $products, array $downloadableProductIds): array
    {
        $attributes = [];

        foreach ($products as $attribute) {
            if (\in_array($attribute['productId'], $downloadableProductIds, true)) {
                $attributes[$attribute['productId']] = ['links_title' => $attribute['linksTitle']];
            }
        }
        return $attributes;
    }
}
