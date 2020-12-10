<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogExport\Model;

use Magento\CatalogExportApi\Api\Data\RatingMetadata;
use Magento\CatalogExportApi\Api\Data\RatingMetadataFactory;
use Magento\CatalogExportApi\Api\EntityRequest;
use Magento\CatalogExportApi\Api\EntityRequest\Item;
use Magento\CatalogExportApi\Api\RatingMetadataRepositoryInterface;
use Magento\DataExporter\Model\FeedPool;
use Magento\Framework\Api\DataObjectHelper;

/**
 * @inheritdoc
 */
class RatingMetadataRepository implements RatingMetadataRepositoryInterface
{
    /**
     * @var RatingMetadataFactory
     */
    private $ratingMetadataFactory;

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
     * @param RatingMetadataFactory $ratingMetadataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param ExportConfiguration $exportConfiguration
     */
    public function __construct(
        FeedPool $feedPool,
        RatingMetadataFactory $ratingMetadataFactory,
        DataObjectHelper $dataObjectHelper,
        ExportConfiguration $exportConfiguration
    ) {
        $this->feedPool = $feedPool;
        $this->ratingMetadataFactory = $ratingMetadataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->exportConfiguration = $exportConfiguration;
    }

    /**
     * @inheritdoc
     */
    public function get(EntityRequest $request): array
    {
        $storeViewCodes = $request->getStoreViewCodes();
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

        $ratingsMetadata = [];
        $feedData = $this->feedPool->getFeed('ratingMetadata')->getFeedByIds($ids, $storeViewCodes);

        foreach ($feedData['feed'] as $feedItem) {
            $ratingMetadata = $this->ratingMetadataFactory->create();
            $feedItem['id'] = $feedItem['ratingId'];
            $this->dataObjectHelper->populateWithArray($ratingMetadata, $feedItem, RatingMetadata::class);
            $ratingsMetadata[] = $ratingMetadata;
        }

        return $ratingsMetadata;
    }
}
