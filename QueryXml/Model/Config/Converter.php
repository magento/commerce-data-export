<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\QueryXml\Model\Config;

use Magento\Framework\Config\ConverterInterface;

/**
 * A converter of reports configuration.
 *
 * Converts configuration data stored in XML format into corresponding PHP array.
 */
class Converter implements ConverterInterface
{
    /**
     * Converts XML node into corresponding array.
     *
     * @param \DOMNode $source
     * @return array|string
     */
    private function convertNode(\DOMNode $source)
    {
        $result = [];
        if ($source->hasAttributes()) {
            $attrs = $source->attributes;
            foreach ($attrs as $attr) {
                $result[$attr->name] = $attr->value;
            }
        }
        if ($source->hasChildNodes()) {
            $children = $source->childNodes;
            if ($children->length == 1) {
                $child = $children->item(0);
                if ($child->nodeType == XML_TEXT_NODE) {
                    $result['_value'] = $child->nodeValue;
                    return count($result) == 1 ? $result['_value'] : $result;
                }
            }
            foreach ($children as $child) {
                if ($child instanceof \DOMCharacterData) {
                    continue;
                }
                $result[$child->nodeName][] = $this->convertNode($child);
            }
        }
        return $result;
    }

    private function buildSourcesMap(array $queryData) : array
    {
        $output = [];
        $alias = isset($queryData['alias']) ? $queryData['alias'] : $queryData['name'];
        $output[$alias] = $queryData['name'];
        if (isset($queryData['link-source'])) {
            foreach ($queryData['link-source'] as $linkedSource) {
                $alias = isset($linkedSource['alias']) ? $linkedSource['alias'] : $linkedSource['name'];
                $output[$alias] = $linkedSource['name'];
            }
        }
        return $output;
    }
    /**
     * Converts XML document into corresponding array.
     *
     * @param \DOMDocument $source
     * @return array
     */
    public function convert($source)
    {
        $configData = $this->convertNode($source);
        if (!isset($configData['config'][0]['query'])) {
            return [];
        }
        $queries = [];
        foreach ($configData['config'][0]['query'] as $queryData) {
            $entityData = array_shift($queryData['source']);
            $queries[$queryData['name']] = $queryData;
            $queries[$queryData['name']]['source'] = $entityData;
            $queries[$queryData['name']]['map'] = $this->buildSourcesMap($entityData);
        }
        return $queries;
    }
}
