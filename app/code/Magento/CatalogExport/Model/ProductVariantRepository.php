<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogExport\Model;

use Magento\CatalogExportApi\Api\Data\ProductVariant;
use Magento\CatalogExportApi\Api\Data\ProductVariantFactory;
use Magento\CatalogExportApi\Api\ProductVariantRepositoryInterface;
use Magento\DataExporter\Model\FeedPool;
use Magento\Framework\App\DeploymentConfig;
use Psr\Log\LoggerInterface;

/**
 * Product variant entity repository
 */
class ProductVariantRepository implements ProductVariantRepositoryInterface
{
    /**
     * Constant value for setting max items in response
     */
    private const MAX_ITEMS_IN_RESPONSE = 250;

    /**
     * @var ProductVariantFactory
     */
    private $productVariantFactory;

    /**
     * @var DtoMapper
     */
    private $dtoMapper;

    /**
     * @var DeploymentConfig
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
     * @param ProductVariantFactory $productVariantFactory
     * @param DtoMapper $dtoMapper
     * @param DeploymentConfig $deploymentConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        FeedPool $feedPool,
        ProductVariantFactory $productVariantFactory,
        DtoMapper $dtoMapper,
        DeploymentConfig $deploymentConfig,
        LoggerInterface $logger
    ) {
        $this->feedPool = $feedPool;
        $this->dtoMapper = $dtoMapper;
        $this->productVariantFactory = $productVariantFactory;
        $this->deploymentConfig = $deploymentConfig;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function get(array $ids): array
    {
        if (count($ids) > $this->getMaxItemsInResponse()) {
            throw new \InvalidArgumentException(
                'Max items in the response can\'t exceed '
                . $this->getMaxItemsInResponse()
                . '.'
            );
        }

        $productsVariants = [];
        $feedData = $this->feedPool->getFeed('variants')->getFeedByIds($ids);
        if (empty($feedData['feed'])) {
            $this->logger->error(
                \sprintf('Cannot find products variants data in catalog feed with ids "%s"', \implode(',', $ids))
            );
            return $productsVariants;
        }

        foreach ($feedData['feed'] as $feedItem) {
            $productVariant = $this->productVariantFactory->create();
            $feedItem = $this->cleanUpNullValues($feedItem);
            $this->dtoMapper->populateWithArray(
                $productVariant,
                $feedItem,
                ProductVariant::class
            );
            $productsVariants[] = $productVariant;
        }
        return $productsVariants;
    }

    /**
     * @inheritdoc
     */
    public function getByProductIds(array $productIds): array
    {
        if (count($productIds) > $this->getMaxItemsInResponse()) {
            throw new \InvalidArgumentException(
                'Max items in the response can\'t exceed '
                . $this->getMaxItemsInResponse()
                . '.'
            );
        }

        $productsVariants = [];
        $feedData = $this->feedPool->getFeed('variants')->getFeedByProductIds($productIds);
        if (empty($feedData['feed'])) {
            $this->logger->error(
                \sprintf(
                    'Cannot find products variants data in catalog feed with product ids "%s"',
                    \implode(',', $productIds)
                )
            );
            return $productsVariants;
        }

        foreach ($feedData['feed'] as $feedItem) {
            $productVariant = $this->productVariantFactory->create();
            $feedItem = $this->cleanUpNullValues($feedItem);
            $this->dtoMapper->populateWithArray(
                $productVariant,
                $feedItem,
                ProductVariant::class
            );
            $productsVariants[] = $productVariant;
        }
        return $productsVariants;
    }

    /**
     * Get max items in response
     *
     * @return int
     */
    private function getMaxItemsInResponse(): int
    {
        try {
            $maxItemsInResponse = (int)$this->deploymentConfig->get('catalog_export/max_items_in_response');
        } catch (\Exception $e) {
            $this->logger->error(
                \sprintf('Cannot retrieve catalog export max items in response for product variants. ' . $e)
            );
            return self::MAX_ITEMS_IN_RESPONSE;
        }
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
