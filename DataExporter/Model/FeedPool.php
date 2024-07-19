<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
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
     * @var FeedInterface[]
     */
    private $registry = [];

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

    /**
     * Returns feed list.
     *
     * @return FeedInterface[]
     */
    public function getList(): array
    {
        if (count($this->registry) < count($this->classMap)) {
            foreach ($this->classMap as $feedName => $feedClass) {
                $this->registry[$feedName] = $this->objectManager->get($feedClass);
            }
        }

        return $this->registry;
    }
}
