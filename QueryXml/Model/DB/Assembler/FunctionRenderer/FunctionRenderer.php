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

namespace Magento\QueryXml\Model\DB\Assembler\FunctionRenderer;

use Magento\Framework\DB\Sql\ColumnValueExpression;
use Magento\Framework\ObjectManagerInterface;
use Magento\QueryXml\Model\DB\SelectBuilder;

/**
 * Composite function renderer
 */
class FunctionRenderer implements FunctionRendererInterface
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var string[]
     */
    private $renderers;

    /**
     * @var string
     */
    private $defaultRendererClassName;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param string[] $renderers
     * @param string $defaultRendererClassName
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        array $renderers = [],
        string $defaultRendererClassName = DefaultFunctionRenderer::class
    ) {
        $this->objectManager = $objectManager;
        $this->renderers = $renderers;
        $this->defaultRendererClassName = $defaultRendererClassName;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $attributeInfo
     * @param array $entityInfo
     * @param SelectBuilder $builder
     * @return ColumnValueExpression
     */
    public function renderFunction(
        array $attributeInfo,
        array $entityInfo,
        SelectBuilder $builder
    ) : ColumnValueExpression {
        if (isset($this->renderers[$attributeInfo['function']])) {
             $renderer = $this->objectManager->get($this->renderers[$attributeInfo['function']]);
        } else {
            $renderer = $this->objectManager->get($this->defaultRendererClassName);
        }
        /** @var FunctionRendererInterface $renderer */
        return $renderer->renderFunction($attributeInfo, $entityInfo, $builder);
    }
}
