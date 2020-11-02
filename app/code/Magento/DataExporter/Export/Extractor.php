<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Export;

use Magento\DataExporter\Export\Request\Info;
use Magento\DataExporter\Export\Request\Node;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class Extractor
 *
 * Extracts data for product.
 */
class Extractor
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * Index data by arguments
     *
     * @param array $field
     * @param array $data
     * @param bool $isRoot
     * @return array
     */
    private function indexDataByArguments(array $field, array $data, bool $isRoot) : array
    {
        $output = [];
        if ($isRoot) {
            return $data;
        }
        if ($field['repeated'] && !$isRoot) {
            foreach ($data as $item) {
                $index = [];
                foreach ($field['using'] as $key) {
                    $index[] = [$key['field'] => $item[$key['field']]];
                }
                $output[base64_encode(json_encode($index))][] = $item[$field['name']];
            }
        } else {
            foreach ($data as $item) {
                $index = [];
                foreach ($field['using'] as $key) {
                    $index[] = [$key['field'] => $item[$key['field']]];
                }
                $output[base64_encode(json_encode($index))] = $item[$field['name']];
            }
        }
        return $output;
    }

    /**
     * Extract data for node
     *
     * @param Info $info
     * @param Node $node
     * @param array $value
     * @return array
     */
    private function extractDataForNode(Info $info, Node $node, array $value)
    {
        $output = [];
        $isRoot = (spl_object_hash($info->getRootNode()) == spl_object_hash($node));
        if ($node->getField()['provider']) {
            $key = base64_encode(json_encode($node->getField()));
            $providerClass = $node->getField()['provider'];
            $provider = $this->objectManager->get($providerClass);

            $data = [];
            if (!empty($value) || $isRoot) {
                $data = $this->indexDataByArguments($node->getField(), $provider->get($value), $isRoot);
                foreach ($node->getChildren() as $child) {
                    $output = array_replace_recursive(
                        $output,
                        $this->extractDataForNode($info, $child, $data)
                    );
                }
            }

            $output[$key] = $data;
        } else {
            foreach ($node->getChildren() as $child) {
                $output = array_replace_recursive(
                    $output,
                    $this->extractDataForNode($info, $child, $value)
                );
            }
        }
        return $output;
    }

    /**
     * Extract data
     *
     * @param Info $info
     * @param array $arguments
     * @return array
     */
    public function extract(Info $info, array $arguments = []) : array
    {
        return $this->extractDataForNode($info, $info->getRootNode(), $arguments);
    }
}
