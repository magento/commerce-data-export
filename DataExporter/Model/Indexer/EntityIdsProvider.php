<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
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
        $affectedIds = [];
        foreach ($resolvers as $resolver) {
            $affectedIds[] = $resolver->getAllAffectedIds($ids);
        }
        return array_unique(array_merge($ids, ...$affectedIds));
    }

    /**
     * @inheritdoc
     */
    public function getAllDeletedIds(FeedIndexMetadata $metadata): ?\Generator
    {
        return $this->allIdsResolver->getAllDeletedIds($metadata);
    }
}
