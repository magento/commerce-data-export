<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

/**
 * Returns IDs needed by indexer for a given feed.
 */
class EntityIdsProvider implements EntityIdsProviderInterface
{
    /**
     * @var AllIdsResolver
     */
    private $allIdsResolver;

    /**
     * @var AffectedIdsResolverPool
     */
    private $affectedIdsResolverPool;

    /**
     * @param AllIdsResolver $allIdsResolver
     * @param AffectedIdsResolverPool $affectedIdsResolverPool
     */
    public function __construct(
        AllIdsResolver $allIdsResolver,
        AffectedIdsResolverPool $affectedIdsResolverPool
    ) {
        $this->allIdsResolver = $allIdsResolver;
        $this->affectedIdsResolverPool = $affectedIdsResolverPool;
    }

    /**
     * @inheritdoc
     *
     * @param FeedIndexMetadata $metadata
     * @return \Generator|null
     */
    public function getAllIds(FeedIndexMetadata $metadata): ?\Generator
    {
        yield from $this->allIdsResolver->getAllIds($metadata);
        yield from $this->allIdsResolver->getAllDeletedIds($metadata);
    }

    /**
     * @inheritdoc
     *
     * @param FeedIndexMetadata $metadata
     * @param array $ids
     * @return array
     */
    public function getAffectedIds(FeedIndexMetadata $metadata, array $ids): array
    {
        $resolvers = $this->affectedIdsResolverPool->getIdsResolversForFeed($metadata->getFeedName());
        foreach ($resolvers as $resolver) {
            $ids = array_merge($ids, $resolver->getAllAffectedIds($ids));
        }
        return $ids;
    }

    /**
     * @inheritdoc
     */
    public function getAllDeletedIds(FeedIndexMetadata $metadata): ?\Generator
    {
        return $this->allIdsResolver->getAllDeletedIds($metadata);
    }
}
