<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QueryXmlDataProvider\Model;

use Magento\DataExporter\Export\Request\Node;
use Magento\DataExporter\Export\Request\Info;

/**
 * Common data provider that can read data for et_schema.xml directly from query.xml
 */
class DataProvider
{

    /**
     * @var \Magento\QueryXml\Model\QueryProcessor
     */
    private $queryProcessor;

    /**
     * @var string|null
     */
    private $queryName;

    /**
     * @var array
     */
    private $queryArguments;

    /**
     * @param QueryProcessor $queryProcessor
     * @param string|null $queryName
     * @param array $queryArguments
     */
    public function __construct(
        QueryProcessor $queryProcessor,
        string $queryName = null,
        array $queryArguments = []
    ) {
        $this->queryProcessor = $queryProcessor;
        $this->queryName = $queryName;
        $this->queryArguments = $queryArguments;
    }

    /**
     * Returns parent node of declared record
     *
     * @param Node $node
     * @param Node $root
     * @return Node
     */
    private function getParentNode(Node $node, Node $root): ?Node
    {
        $parent = null;
        foreach ($root->getChildren() as $child) {
            if (spl_object_hash($child) == spl_object_hash($node)) {
                return $root;
            }
            $parent = $this->getParentNode($node, $child);
        }
        return $parent;
    }

    /**
     * Check if field repeated
     *
     * @param Node $node
     * @return bool
     */
    private function isRepeated(Node $node): bool
    {
        return $node->getField()['repeated'] ?? false;
    }

    /**
     * Prepare query arguments from record
     *
     * @param array $values
     * @param Node $node
     * @param Info $info
     * @return array
     */
    private function getQueryArguments(array $values, Node $node, Info $info): array
    {
        $argumentList = array_merge($node->getField()['using'], $this->queryArguments);
        $arguments = [];
        $parent = $this->getParentNode($node, $info->getRootNode());
        foreach ($values as $value) {
            foreach ($argumentList as $argument) {
                if ($this->isRoot($node, $info) || $this->isRoot($parent, $info) || !$this->isRepeated($parent)) {
                    if (isset($value[$argument['field']])) {
                        $arguments[$argument['field']] = $value[$argument['field']];
                    }
                } else {
                    foreach ($value as $item) {
                        if (isset($item[$argument['field']])) {
                            $arguments[$argument['field']] = $item[$argument['field']];
                        }
                    }
                }
            }
        }
        return $arguments;
    }

    /**
     * Check if node is root of record
     *
     * @param Node $node
     * @param Info $info
     * @return bool
     */
    private function isRoot(Node $node, Info $info): bool
    {
        return (spl_object_hash($info->getRootNode()) == spl_object_hash($node));
    }

    /**
     * Get index fields for a node
     *
     * @param Node $node
     * @return array
     */
    private function getNodeIndexFields(Node $node): array
    {
        $field = $node->getField();
        $indexFields = [];
        foreach ($field['using'] as $item) {
            $indexFields[] = $item['field'];
        }
        if ($node->getId() != null) {
            $indexFields[] = $node->getId();
        }
        return $indexFields;
    }

    /**
     * Get data from query
     *
     * @param array $values
     * @param Node $node
     * @param Info $info
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function get(array $values, Node $node, Info $info): array
    {
        $field = $node->getField();
        $isRoot = (spl_object_hash($info->getRootNode()) == spl_object_hash($node));
        $arguments = $this->getQueryArguments($values, $node, $info);
        $queryName = $this->queryName ?? $field['name'];
        if (!$isRoot) {
            $result = null;
            $cursor = $this->queryProcessor->execute($queryName, $arguments);
            while ($row = $cursor->fetch()) {
                $index = [];
                foreach ($this->getNodeIndexFields($node) as $indexField) {
                    $index[$indexField] = $row[$indexField];
                }
                $result[json_encode($index)] = array_merge($index, [$field['name'] => $row]);
            }
        } else {
            $result = $this->queryProcessor->execute($queryName, $arguments)->fetchAll();
        }
        return $result;
    }
}
