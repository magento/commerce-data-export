<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Export\Request;

/**
 * Class Info
 *
 * DTO class for node info.
 */
class Info
{
    /**
     * @var Node
     */
    private $node;

    /**
     * @param Node $node
     */
    public function __construct(
        Node $node
    ) {
        $this->node = $node;
    }

    /**
     * Get root node.
     *
     * @return Node
     */
    public function getRootNode() : Node
    {
        return $this->node;
    }
}
