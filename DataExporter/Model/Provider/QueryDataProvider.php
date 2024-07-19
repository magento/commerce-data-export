<?php
/**
 * Copyright 2023 Adobe
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

namespace Magento\DataExporter\Model\Provider;

use Magento\DataExporter\Exception\UnableSendData;
use Magento\DataExporter\Export\Request\Node;
use Magento\DataExporter\Export\Request\Info;
use Magento\QueryXml\Model\QueryProcessor;

/**
 * Common data provider that can read data for et_schema.xml directly from query.xml
 */
class QueryDataProvider
{

    /**
     * @var QueryProcessor
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
            if (spl_object_hash($child) === spl_object_hash($node)) {
                return $root;
            }
            $parent = $this->getParentNode($node, $child);
            if (null !== $parent) {
                return $parent;
            }
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
        $argumentList = $node->getField()['using'];

        $arguments = [];
        $parent = $this->getParentNode($node, $info->getRootNode());
        $isSingleValue = $this->isRoot($node, $info)
            || ($parent && $this->isRoot($parent, $info))
            || ($parent && !$this->isRepeated($parent));
        foreach ($values as $value) {
            foreach ($argumentList as $argument) {
                if ($isSingleValue) {
                    if (isset($value[$argument['field']])) {
                        $arguments[$argument['field']][] = $value[$argument['field']];
                    }
                } else {
                    foreach ($value as $item) {
                        if (isset($item[$argument['field']])) {
                            $arguments[$argument['field']][] = $item[$argument['field']];
                        }
                    }
                }
            }
        }
        $arguments+= $this->queryArguments;
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
        return (spl_object_hash($info->getRootNode()) === spl_object_hash($node));
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
        $isRoot = (spl_object_hash($info->getRootNode()) === spl_object_hash($node));
        $arguments = $this->getQueryArguments($values, $node, $info);
        $queryName = $this->queryName ?? $field['name'];
        if (!$isRoot) {
            $result = [];
            $cursor = $this->queryProcessor->execute($queryName, $arguments);
            $nodeIndexFields = $this->getNodeIndexFields($node);
            while ($row = $cursor->fetch()) {
                $index = [];
                foreach ($nodeIndexFields as $indexField) {
                    if(!isset($row[$indexField])) {
                        $errormsg = __(
                            'Data mismatch in query "%1", expected indexField: "%2". row: "%3". Verify query.xml configuration and clean data cache.',
                            $queryName,
                            $indexField,
                            var_export($row, true)
                        );
                        throw new UnableSendData($errormsg);
                    }
                    $index[$indexField] = $row[$indexField];
                }
                $result[] = array_merge($index, [$field['name'] => $row]);
            }
        } else {
            $result = $this->queryProcessor->execute($queryName, $arguments)->fetchAll();
        }
        return $result;
    }
}
