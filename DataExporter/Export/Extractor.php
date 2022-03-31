<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Export;

use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\DataExporter\Export\Request\Info;
use Magento\DataExporter\Export\Request\Node;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
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
     * @var bool
     */
    private $profiler;

    /**
     * @var CommerceDataExportLoggerInterface
     */
    private $logger;

    /**
     * @var float
     */
    private $profilerTime;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param CommerceDataExportLoggerInterface $logger
     * @param bool $profiler
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        CommerceDataExportLoggerInterface $logger,
        bool $profiler = false
    ) {
        $this->objectManager = $objectManager;
        $this->logger = $logger;
        $this->profiler = $profiler;
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
                $output[LookupBuilder::build($field, $item)][] = $item[$field['name']];
            }
        } else {
            foreach ($data as $item) {
                $output[LookupBuilder::build($field, $item)] = $item[$field['name']];
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
        $isRoot = (spl_object_hash($info->getRootNode()) === spl_object_hash($node));
        if ($node->getField()['provider']) {
            $key = base64_encode(json_encode($node->getField()));
            $providerClass = $node->getField()['provider'];
            $provider = $this->objectManager->get($providerClass);

            $data = [];
            if (!empty($value) || $isRoot) {
                $this->profilerStart();
                $data = $this->indexDataByArguments(
                    $node->getField(),
                    array_values($provider->get($value, $node, $info)),
                    $isRoot
                );
                $this->profilerStop($isRoot, $providerClass, $value);
                foreach ($node->getChildren() as $child) {
                    try {
                        $output = array_replace_recursive(
                            $output,
                            $this->extractDataForNode($info, $child, $data)
                        );
                    } catch (\Throwable $e) {
                        throw new UnableRetrieveData(
                            "child provider: " . $child->getField()['provider'],
                            0,
                            $e
                        );
                    }
                }
            }

            $output[$key] = $data;
        } else {
            foreach ($node->getChildren() as $child) {
                try {
                $output = array_replace_recursive(
                    $output,
                    $this->extractDataForNode($info, $child, $value)
                );
                } catch (\Throwable $e) {
                    throw new UnableRetrieveData(
                        "child provider: " . $child->getField()['provider'],
                        0,
                        $e
                    );
                }
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

    /**
     * Stop profiling
     *
     * @param bool $isRoot
     * @param string $providerClass
     * @param array $value
     */
    private function profilerStop(bool $isRoot, string $providerClass, array $value): void
    {
        if (!$this->profiler) {
            return ;
        }
        $prefix = $isRoot ? '' : '|_ ';
        $this->logger->info(
            $prefix . $providerClass
            . ', ' . count($value)
            . ', ' . round((microtime(true) - $this->profilerTime), 5)
            . ', ' . memory_get_peak_usage(true) / 1024 / 1024
        );
    }

    /**
     * Start profiling
     *
     * @return void
     */
    private function profilerStart(): void
    {
        if ($this->profiler) {
            $this->profilerTime = microtime(true);
        }
    }
}
