<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogExport\Model;

use Magento\CatalogExportApi\Api\Data\ReviewFactory;
use Magento\CatalogExportApi\Api\Data\Review;
use Magento\CatalogExportApi\Api\EntityRequest;
use Magento\CatalogExportApi\Api\EntityRequest\Item;
use Magento\CatalogExportApi\Api\ReviewRepositoryInterface;
use Magento\DataExporter\Model\FeedPool;
use Magento\Framework\Api\DataObjectHelper;

/**
 * @inheritdoc
 */
class ReviewRepository implements ReviewRepositoryInterface
{
    /**
     * @var ReviewFactory
     */
    private $reviewFactory;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var ExportConfiguration
     */
    private $exportConfiguration;

    /**
     * @var FeedPool
     */
    private $feedPool;

    /**
     * @param FeedPool $feedPool
     * @param ReviewFactory $reviewFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param ExportConfiguration $exportConfiguration
     */
    public function __construct(
        FeedPool $feedPool,
        ReviewFactory $reviewFactory,
        DataObjectHelper $dataObjectHelper,
        ExportConfiguration $exportConfiguration
    ) {
        $this->feedPool = $feedPool;
        $this->reviewFactory = $reviewFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->exportConfiguration = $exportConfiguration;
    }

    /**
     * @inheritdoc
     */
    public function get(EntityRequest $request): array
    {
        $ids = \array_map(function (Item $item) {
            return $item->getEntityId();
        }, $request->getEntities());

        if (count($ids) > $this->exportConfiguration->getMaxItemsInResponse()) {
            throw new \InvalidArgumentException(
                'Max items in the response can\'t exceed '
                . $this->exportConfiguration->getMaxItemsInResponse()
                . '.'
            );
        }

        $reviews = [];
        $feedData = $this->feedPool->getFeed('reviews')->getFeedByIds($ids);

        foreach ($feedData['feed'] as $feedItem) {
            $review = $this->reviewFactory->create();
            $feedItem['id'] = $feedItem['reviewId'];
            $this->dataObjectHelper->populateWithArray($review, $feedItem, Review::class);
            $reviews[] = $review;
        }

        return $reviews;
    }
}
