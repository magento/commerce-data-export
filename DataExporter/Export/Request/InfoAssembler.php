<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Export\Request;

use Magento\DataExporter\Config\ConfigInterface;

/**
 * Class InfoAssembler
 *
 * Assembles field nodes information.
 */
class InfoAssembler
{
    /**
     * @var array
     */
    private $cache;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var NodeFactory
     */
    private $nodeFactory;

    /**
     * @var InfoFactory
     */
    private $infoFactory;

    /**
     * @param ConfigInterface $config
     * @param NodeFactory $nodeFactory
     * @param InfoFactory $infoFactory
     */
    public function __construct(
        ConfigInterface $config,
        NodeFactory $nodeFactory,
        InfoFactory $infoFactory
    ) {
        $this->config = $config;
        $this->nodeFactory = $nodeFactory;
        $this->infoFactory = $infoFactory;
    }

    /**
     * Assemble node
     *
     * @param array $field
     * @return Node
     */
    private function assembleNode(array $field) : Node
    {
        $children = [];
        if ($this->isScalar($field['type'])) {
            return $this->nodeFactory->create(
                [
                    'field' => $field,
                    'children' => []
                ]
            );
        } else {
            $type = $this->config->get($field['type']);
            foreach ($type['field'] as $item) {
                if (isset($item['provider']) || !$this->isScalar($item['type'])) {
                    $children[$item['name']] = $this->assembleNode($item);
                }
            }
            return $this->nodeFactory->create(
                [
                    'field' => $field,
                    'children' => $children,
                    'id' => $type['ID'] ?? null
                ]
            );
        }
    }

    /**
     * Assemble field info
     *
     * @param string $fieldName
     * @param string $parentTypeName
     * @return Info
     */
    public function assembleFieldInfo(string $fieldName, string $parentTypeName) : Info
    {
        $cacheKey = $fieldName . $parentTypeName;
        if (!isset($this->cache[$cacheKey])) {
            $export = $this->config->get($parentTypeName);
            $node = $this->assembleNode($export['field'][$fieldName]);
            $this->cache[$cacheKey] = $this->infoFactory->create(['node' => $node]);
        }
        return $this->cache[$cacheKey];
    }

    /**
     * Check if type is scalar
     *
     * @param string $typeName
     * @return bool
     */
    private function isScalar(string $typeName) : bool
    {
        return $this->config->isScalar($typeName);
    }
}
