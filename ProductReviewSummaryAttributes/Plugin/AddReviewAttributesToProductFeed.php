<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\ProductReviewSummaryAttributes\Plugin;

use Magento\DataExporter\Export\Processor;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use AdobeCommerce\ProductReviewSummaryAttributes\Api\ReviewSummarySourceInterface;
use AdobeCommerce\ProductReviewSummaryAttributes\Model\ReviewAttributeContract;
use AdobeCommerce\ProductReviewSummaryAttributes\Model\ReviewSummaryAttributeRegistry;

/**
 * Adds review summary values to the products feed and applies metadata overrides to the product attributes feed.
 *
 * - Nothing happens while the review summary source is disabled (default).
 * - Attributes are created only when the first product with an approved review is exported, so merchants
 *   without reviews see no new attributes and no feed changes.
 * - Values are added only to products that have a review summary. A summary with 0 approved reviews is still
 *   exported, so an N -> 0 transition updates the value downstream.
 * - Values are added at export time and never read from EAV, so product saves cannot overwrite them.
 * - Errors are logged and rethrown, so the feed indexer keeps the batch in the changelog and retries it instead of
 *   exporting the products without their review summary values.
 */
class AddReviewAttributesToProductFeed
{
    private const PRODUCTS_FEED = 'products';

    private const PRODUCT_ATTRIBUTES_FEED = 'productAttributes';

    /**
     * @param ReviewSummarySourceInterface $reviewSummarySource
     * @param ReviewSummaryAttributeRegistry $attributeRegistry
     * @param ReviewAttributeContract $contract
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        private readonly ReviewSummarySourceInterface $reviewSummarySource,
        private readonly ReviewSummaryAttributeRegistry $attributeRegistry,
        private readonly ReviewAttributeContract $contract,
        private readonly CommerceDataExportLoggerInterface $logger
    ) {
    }

    /**
     * Enrich products and product attributes feeds.
     *
     * @param Processor $processor
     * @param array $feedItems
     * @param string $feedName
     * @return array
     * @throws \Throwable
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterProcess(Processor $processor, array $feedItems, string $feedName): array
    {
        if (empty($feedItems)
            || ($feedName !== self::PRODUCTS_FEED && $feedName !== self::PRODUCT_ATTRIBUTES_FEED)
        ) {
            return $feedItems;
        }

        try {
            return $feedName === self::PRODUCT_ATTRIBUTES_FEED
                ? $this->applyAttributeMetadataOverrides($feedItems)
                : $this->addReviewSummaryValues($feedItems);
        } catch (\Throwable $e) {
            $this->logger->error(
                'CDE01-24 Unable to add review summary data to feed',
                ['feed' => $feedName, 'error' => $e->getMessage(), 'exception' => $e]
            );
            throw $e;
        }
    }

    /**
     * Apply configured overrides to the review summary attributes metadata.
     *
     * Attributes with the same code created by a merchant are not changed.
     *
     * @param array $productAttributes
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function applyAttributeMetadataOverrides(array $productAttributes): array
    {
        $attributeCodes = $this->attributeRegistry->getAttributeCodes();
        $reviewAttributes = array_filter(
            $productAttributes,
            static fn (array $attribute) => in_array($attribute['attributeCode'] ?? null, $attributeCodes, true)
        );
        if (empty($reviewAttributes) || !$this->attributeRegistry->isRegistered()) {
            return $productAttributes;
        }

        foreach ($reviewAttributes as $index => $attribute) {
            foreach ($this->attributeRegistry->getExporterOverride($attribute['attributeCode']) as $field => $value) {
                $productAttributes[$index][$field] = $value;
            }
        }

        return $productAttributes;
    }

    /**
     * Add review summary values to products that have a review summary.
     *
     * @param array $products
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function addReviewSummaryValues(array $products): array
    {
        if (!$this->reviewSummarySource->isEnabled()) {
            return $products;
        }
        [$productIds, $storeViewCodes] = $this->collectScope($products);
        if (empty($productIds)) {
            return $products;
        }
        $summaries = $this->reviewSummarySource->getSummaries($productIds, $storeViewCodes);
        if (empty($summaries) || !$this->ensureAttributesRegistered($summaries)) {
            return $products;
        }

        foreach ($products as $index => $product) {
            if (!isset($product['productId'], $product['storeViewCode'])) {
                continue;
            }
            $summary = $summaries[$this->contract->key($product['productId'], $product['storeViewCode'])] ?? null;
            if ($summary === null) {
                continue;
            }
            $attributes = $this->removeReviewAttributes($product['attributes'] ?? []);
            $attributes[] = [
                'attributeCode' => ReviewAttributeContract::ATTRIBUTE_REVIEW_COUNT,
                'value' => [(string)(int)$summary['reviewCount']],
            ];
            $attributes[] = [
                'attributeCode' => ReviewAttributeContract::ATTRIBUTE_RATING_SUMMARY,
                'value' => [(string)round((float)$summary['ratingSummary'], 2)],
            ];
            $products[$index]['attributes'] = $attributes;
        }

        return $products;
    }

    /**
     * Make sure attributes exist before values are exported.
     *
     * Attributes are created only when at least one product has an approved review: a pending review may
     * already produce a summary with 0 approved reviews, which must not create the attributes.
     *
     * @param array $summaries
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function ensureAttributesRegistered(array $summaries): bool
    {
        if ($this->attributeRegistry->isRegistered()) {
            return true;
        }
        if (!$this->hasApprovedReviews($summaries)) {
            return false;
        }
        $this->attributeRegistry->register();
        if (!$this->attributeRegistry->isRegistered()) {
            $this->logger->warning(
                'CDE01-26 Review summary attributes are not registered. Values are not exported. '
                . 'Check that no attribute with the same code was created by a merchant',
                ['attributeCodes' => $this->attributeRegistry->getAttributeCodes()]
            );

            return false;
        }

        return true;
    }

    /**
     * Check if at least one summary has approved reviews.
     *
     * @param array $summaries
     * @return bool
     */
    private function hasApprovedReviews(array $summaries): bool
    {
        foreach ($summaries as $summary) {
            if ((int)$summary['reviewCount'] > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove review summary attributes that may already be present in the product feed item.
     *
     * @param array $attributes
     * @return array
     */
    private function removeReviewAttributes(array $attributes): array
    {
        return array_values(array_filter(
            $attributes,
            static fn ($attribute) => !in_array(
                $attribute['attributeCode'] ?? null,
                ReviewAttributeContract::ATTRIBUTE_CODES,
                true
            )
        ));
    }

    /**
     * Collect distinct product ids and store view codes of the feed items.
     *
     * @param array $products
     * @return array{0: int[], 1: string[]}
     */
    private function collectScope(array $products): array
    {
        $productIds = [];
        $storeViewCodes = [];
        foreach ($products as $product) {
            if (isset($product['productId'], $product['storeViewCode'])) {
                $productIds[(int)$product['productId']] = (int)$product['productId'];
                $storeViewCodes[$product['storeViewCode']] = (string)$product['storeViewCode'];
            }
        }

        return [array_values($productIds), array_values($storeViewCodes)];
    }
}
