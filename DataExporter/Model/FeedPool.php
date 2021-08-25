<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model;

use Magento\Framework\ObjectManagerInterface;

/**
 * Pool of all existing feed providers
 */
class FeedPool
{
    /**
     * @var array
     */
    private $registry;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var array
     */
    private $classMap;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param array $classMap
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        array $classMap = []
    ) {
        $this->objectManager = $objectManager;
        $this->classMap = $classMap;
    }

    /**
     * Returns feed object
     *
     * @param string $feedName
     * @return FeedInterface
     * @throws \InvalidArgumentException
     */
    public function getFeed(string $feedName) : FeedInterface
    {
        if (!isset($this->classMap[$feedName])) {
            throw new \InvalidArgumentException(
                \sprintf('Not registered Feed "%s"', $feedName)
            );
        }
        if (!isset($this->registry[$feedName])) {
            $this->registry[$feedName] = $this->objectManager->get($this->classMap[$feedName]);
        }
        return $this->registry[$feedName];
    }
}
