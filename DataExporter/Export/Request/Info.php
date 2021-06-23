<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
