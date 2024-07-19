<?php
/**
 * Copyright 2021 Adobe
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

namespace Magento\ProductVariantDataExporter\Model\Provider\ProductVariants;

use Magento\Framework\ObjectManagerInterface;

/**
 * Pool of all existing product variant option value providers
 */
class OptionValueFactory
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
     * Returns product variant option value provider object
     *
     * @param string $typeName
     * @return OptionValueInterface
     * @throws \InvalidArgumentException
     */
    public function get(string $typeName): OptionValueInterface
    {
        if (!isset($this->variantTypes[$typeName])) {
            throw new \InvalidArgumentException(
                \sprintf('Product variant option value provider for type %s not registered', $typeName)
            );
        }
        if (!isset($this->registry[$typeName])) {
            $this->registry[$typeName] = $this->objectManager->get($this->variantTypes[$typeName]);
        }
        return $this->registry[$typeName];
    }
}
