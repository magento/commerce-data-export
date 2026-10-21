<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\ProductReviewSummarySync\Model;

use AdobeCommerce\ProductReviewSummaryAttributes\Api\ReviewSummarySourceInterface;
use AdobeCommerce\ProductReviewSummaryAttributes\Model\ReviewAttributeContract;
use AdobeCommerce\ProductReviewSummarySync\Model\Query\ReviewSummaryQuery;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Review\Model\Review\Config as ReviewConfig;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Native Magento review summary source (review_entity_summary).
 *
 * Summaries are exported only for store views where "Enable Product Reviews" (catalog/review/active) is on.
 */
class ReviewSummarySource implements ReviewSummarySourceInterface
{
    /**
     * @param ResourceConnection $resourceConnection
     * @param ReviewSummaryQuery $reviewSummaryQuery
     * @param ReviewAttributeContract $contract
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly ReviewSummaryQuery $reviewSummaryQuery,
        private readonly ReviewAttributeContract $contract,
        private readonly StoreManagerInterface $storeManager,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Reviews are enabled in at least one store view.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return !empty($this->getEnabledStoreViewCodes());
    }

    /**
     * @inheritDoc
     */
    public function getSummaries(array $productIds, array $storeViewCodes): array
    {
        $storeViewCodes = array_values(array_intersect($storeViewCodes, $this->getEnabledStoreViewCodes()));
        if (empty($productIds) || empty($storeViewCodes)) {
            return [];
        }

        $rows = $this->resourceConnection->getConnection()->fetchAll(
            $this->reviewSummaryQuery->getSummariesSelect($productIds, $storeViewCodes)
        );
        $summaries = [];
        foreach ($rows as $row) {
            $summaries[$this->contract->key($row['productId'], $row['storeViewCode'])] = [
                'reviewCount' => (int)$row['reviewCount'],
                'ratingSummary' => (float)$row['ratingSummary'],
            ];
        }

        return $summaries;
    }

    /**
     * Get codes of the store views where product reviews are enabled.
     *
     * @return string[]
     */
    private function getEnabledStoreViewCodes(): array
    {
        $storeViewCodes = [];
        foreach ($this->storeManager->getStores() as $store) {
            if ($this->scopeConfig->isSetFlag(
                ReviewConfig::XML_PATH_REVIEW_ACTIVE,
                ScopeInterface::SCOPE_STORE,
                $store->getId()
            )) {
                $storeViewCodes[] = (string)$store->getCode();
            }
        }

        return $storeViewCodes;
    }
}
