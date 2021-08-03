<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
