<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product\CustomizableOptions;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Class for fetching product options.
 */
class Repository
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Catalog\Api\Data\ProductCustomOptionInterface[]
     */
    private $productOptions;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Returns product options for give products
     *
     * @param int[] $productIds
     * @return array
     */
    public function get(array $productIds): array
    {
        $cacheKey = $this->getCacheKey($productIds);

        if (!isset($this->productOptions[$cacheKey])) {
            $productOptions = [];

            $this->searchCriteriaBuilder->addFilter('entity_id', $productIds, 'in');
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $products = $this->productRepository->getList($searchCriteria)->getItems();

            foreach ($products as $product) {
                $productOptions[$product->getId()] = $product->getOptions();
            }

            $this->productOptions[$cacheKey] = $productOptions;
        }

        return $this->productOptions[$cacheKey];
    }

    /**
     * Get cache key based on given ids.
     *
     * @param array $ids
     * @return string
     */
    private function getCacheKey(array $ids): string
    {
        return \sha1(json_encode($ids));
    }
}
