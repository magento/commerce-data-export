<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogExport\Model;

use Magento\CatalogExportApi\Api\EntityRequest;
use Magento\CatalogExportApi\Api\ProductRepositoryInterface;
use Magento\DataExporter\Model\FeedPool;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ProductRepository implements ProductRepositoryInterface
{
    /**
     * Constant value for setting max items in response
     */
    private const MAX_ITEMS_IN_RESPONSE = 250;

    /**
     * @var \Magento\CatalogExportApi\Api\Data\ProductFactory
     */
    private $productFactory;

    /**
     * @var DtoMapper
     */
    private $dtoMapper;

    /**
     * @var \Magento\Framework\App\DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FeedPool
     */
    private $feedPool;

    /**
     * @param FeedPool $feedPool
     * @param \Magento\CatalogExportApi\Api\Data\ProductFactory $productFactory
     * @param DtoMapper $dtoMapper
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        FeedPool $feedPool,
        \Magento\CatalogExportApi\Api\Data\ProductFactory $productFactory,
        DtoMapper $dtoMapper,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        LoggerInterface $logger
    ) {
        $this->feedPool = $feedPool;
        $this->dtoMapper = $dtoMapper;
        $this->productFactory = $productFactory;
        $this->deploymentConfig = $deploymentConfig;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function get(EntityRequest $request)
    {
        $storeViewCodes = $request->getStoreViewCodes();
        $attributesArray = $this->getAttributesArray($request);
        $ids = \array_keys($attributesArray);

        if (count($ids) > $this->getMaxItemsInResponse()) {
            throw new \InvalidArgumentException(
                'Max items in the response can\'t exceed '
                    . $this->getMaxItemsInResponse()
                    . '.'
            );
        }

        $products = [];
        $feedData = $this->feedPool->getFeed('products')->getFeedByIds($ids, $storeViewCodes, $attributesArray);
        if (empty($feedData['feed'])) {
            $this->logger->error(
                \sprintf('Cannot find products data in catalog feed with ids "%s"', \implode(',', $ids))
            );
            return $products;
        }

        foreach ($feedData['feed'] as $feedItem) {
            $product = $this->productFactory->create();
            $feedItem['id'] = $feedItem['productId'];
            $feedItem = $this->cleanUpNullValues($feedItem);

            // TODO Partial index should expose only changed attributes.
            $this->dtoMapper->populateWithArray(
                $product,
                $feedItem,
                \Magento\CatalogExportApi\Api\Data\Product::class
            );
            $products[] = $product;
        }
        return $products;
    }

    /**
     * Retrieve transformed request attributes data (entity_id => attributes)
     *
     * @param EntityRequest $request
     *
     * @return array
     */
    private function getAttributesArray(EntityRequest $request): array
    {
        $attributesArray = [];
        foreach ($request->getEntities() as $entity) {
            $attributesArray[$entity->getEntityId()] = $entity->getAttributeCodes();
        }

        return $attributesArray;
    }

    /**
     * Get max items in response
     *
     * @return int
     */
    private function getMaxItemsInResponse()
    {
        $maxItemsInResponse = (int) $this->deploymentConfig->get('catalog_export/max_items_in_response');
        return $maxItemsInResponse ?: self::MAX_ITEMS_IN_RESPONSE;
    }

    /**
     * Unset null values in provided array recursively
     *
     * @param array $array
     * @return array
     */
    private function cleanUpNullValues(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $result[$key] = is_array($value) ? $this->cleanUpNullValues($value) : $value;
        }
        return $result;
    }
}
