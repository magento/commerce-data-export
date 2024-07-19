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
namespace Magento\QueryXml\Model\DB;

use Magento\Framework\ObjectManagerInterface;

/**
 * Factory class for @see \Magento\QueryXml\Model\DB\SelectBuilder
 */
class SelectBuilderFactory
{
    /**
     * Object Manager instance
     *
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * SelectBuilderFactory constructor.
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * Create class instance with specified parameters
     *
     * @param array $data
     * @return SelectBuilder
     */
    public function create(array $data = [])
    {
        return $this->objectManager->create(SelectBuilder::class, $data);
    }
}
