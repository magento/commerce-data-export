<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

use Magento\DataExporter\Export\Processor as ExportProcessor;
use Magento\Framework\App\ResourceConnection;

/**
 * Feed indexer processor strategy, support creation, updates and deletion of an entity
 */
class FeedIndexProcessorCreateUpdateDelete extends FeedIndexProcessorCreateUpdate implements FeedIndexProcessorInterface
{
    /**
     * @var MarkRemovedEntitiesInterface
     */
    private $markRemovedEntities;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ExportProcessor $exportProcessor
     * @param MarkRemovedEntitiesInterface $markRemovedEntities
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ExportProcessor $exportProcessor,
        MarkRemovedEntitiesInterface $markRemovedEntities
    ) {
        parent::__construct($resourceConnection, $exportProcessor);
        $this->markRemovedEntities = $markRemovedEntities;
    }

    /**
     * @inheridoc
     *
     * @param FeedIndexMetadata $metadata
     * @param DataSerializerInterface $serializer
     * @param EntityIdsProviderInterface $idsProvider
     * @param array $ids
     */
    public function partialReindex(FeedIndexMetadata $metadata, DataSerializerInterface $serializer, EntityIdsProviderInterface $idsProvider, array $ids = []): void
    {
        parent::partialReindex($metadata, $serializer, $idsProvider, $ids);
        $this->markRemovedEntities->execute($ids, $metadata);
    }
}
