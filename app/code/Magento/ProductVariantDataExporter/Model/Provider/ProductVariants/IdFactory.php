<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Model\Provider\ProductVariants;

use Magento\Framework\ObjectManagerInterface;

/**
 * Pool of all existing product variant id providers
 */
class IdFactory
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
    private $variantTypes;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param array $variantTypes
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        array $variantTypes = []
    ) {
        $this->objectManager = $objectManager;
        $this->variantTypes = $variantTypes;
    }

    /**
     * Returns product variant id provider object
     *
     * @param string $typeName
     * @return IdInterface
     * @throws \InvalidArgumentException
     */
    public function get(string $typeName): IdInterface
    {
        if (!isset($this->variantTypes[$typeName])) {
            throw new \InvalidArgumentException(
                \sprintf('Product variant id provider for type %s not registered', $typeName)
            );
        }
        if (!isset($this->registry[$typeName])) {
            $this->registry[$typeName] = $this->objectManager->get($this->variantTypes[$typeName]);
        }
        return $this->registry[$typeName];
    }
}
