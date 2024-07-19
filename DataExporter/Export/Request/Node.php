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

namespace Magento\DataExporter\Export\Request;

/**
 * Class Node
 *
 * DTO class for node.
 */
class Node
{
    /**
     * @var array
     */
    private $field;

    /**
     * @var array
     */
    private $children;

    /**
     * @var ?string
     */
    private $id;

    /**
     * @param array $field
     * @param array $children
     * @param string|null $id
     */
    public function __construct(
        array $field,
        array $children,
        ?string $id = null
    ) {
        $this->field = $field;
        $this->children = $children;
        $this->id = $id;
    }

    /**
     * Get child nodes
     *
     * @return Node[]
     */
    public function getChildren() : array
    {
        return $this->children;
    }

    /**
     * Get field
     *
     * @return array
     */
    public function getField() : array
    {
        return $this->field;
    }

    /**
     * Get ID name
     *
     * @return ?string
     */
    public function getId(): ?string
    {
        return $this->id;
    }
}
