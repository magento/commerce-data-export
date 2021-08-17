<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
