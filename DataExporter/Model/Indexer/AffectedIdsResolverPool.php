<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

use Magento\Framework\ObjectManagerInterface;

/**
 * Pull for IDs resolvers, returns resolver for feed per feed name, can be configured via di.xml
 */
class AffectedIdsResolverPool
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var AffectedIdsResolverInterface[]
     */
    private $resolvers;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param array $resolvers
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        array $resolvers = []
    ) {
        $this->objectManager = $objectManager;
        $this->resolvers = $resolvers;
    }

    /**
     * Returns array of ID resolvers declared for feed by name
     *
     * @param string $feedName
     * @return AffectedIdsResolverInterface[]
     */
    public function getIdsResolversForFeed(string $feedName): array
    {
        $output = [];
        if (isset($this->resolvers[$feedName]) && is_array($this->resolvers[$feedName])) {
            foreach ($this->resolvers[$feedName] as $resolverName) {
                $resolver = $this->objectManager->get($resolverName);
                if ( $resolver instanceof AffectedIdsResolverInterface) {
                    $output[] = $this->objectManager->get($resolverName);
                } else {
                    throw new \InvalidArgumentException(
                        "All resolvers MUST implement " . AffectedIdsResolverInterface::class
                    );
                }
            }
        }
        return $output;
    }
}
