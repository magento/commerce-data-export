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

namespace Magento\QueryXml\Model\Config;

use DOMDocument;
use Magento\Framework\Config\ConverterInterface;

/**
 * A converter of reports configuration.
 *
 * Converts configuration data stored in XML format into corresponding PHP array.
 */
class Converter implements ConverterInterface
{
    /**
     * @var ConverterInterface
     */
    private $baseConverter;

    /**
     * @param ConverterInterface $converter
     */
    public function __construct(ConverterInterface $converter)
    {
        $this->baseConverter = $converter;
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
     * @param DOMDocument $source
     * @return array
     */
    public function convert($source)
    {
        $configData = $this->baseConverter->convert($source);
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
