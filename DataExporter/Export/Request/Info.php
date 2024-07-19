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
