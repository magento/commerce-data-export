<?php
/**
 * Copyright 2024 Adobe
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

namespace Magento\DataExporter\Export;

use Magento\DataExporter\Export\Request\InfoAssembler;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * Class Processor
 *
 * Processes data for given field from et_schema
 */
class Processor
{
    /**
     * @var Extractor
     */
    private $extractor;

    /**
     * @var Transformer
     */
    private $transformer;

    /**
     * @var InfoAssembler
     */
    private $infoAssembler;

    /**
     * @var string
     */
    private $rootProfileName;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Extractor $extractor
     * @param Transformer $transformer
     * @param InfoAssembler $infoAssembler
     * @param LoggerInterface $logger
     * @param string $rootProfileName
     */
    public function __construct(
        Extractor $extractor,
        Transformer $transformer,
        InfoAssembler $infoAssembler,
        LoggerInterface $logger,
        string $rootProfileName = 'Export'
    ) {
        $this->extractor = $extractor;
        $this->transformer = $transformer;
        $this->infoAssembler = $infoAssembler;
        $this->rootProfileName = $rootProfileName;
        $this->logger = $logger;
    }

    /**
     * Process data inside callback
     *
     * @param FeedIndexMetadata $metadata
     * @param array $arguments
     * @param callable $dataProcessorCallback
     * @param bool $lastChunk
     * @return void
     */
    public function processWithCallback(
        FeedIndexMetadata $metadata,
        array $arguments,
        callable $dataProcessorCallback,
        $lastChunk = false
    ) : void {
        try {
            $info = $this->infoAssembler->assembleFieldInfo($metadata->getFeedName(), $this->rootProfileName);

            $dataProcessorCallback = function ($snapshots) use ($dataProcessorCallback, $info) {

                $dataProcessorCallback($this->transformer->transform($info, $snapshots));
            };
            $this->extractor->extractWithCallback($info, $arguments, $dataProcessorCallback, $metadata, $lastChunk);
        } catch (\Throwable $exception) {
            $provider = empty($info) === false ? $info->getRootNode()->getField()['provider'] : '';
            // if error happened during data collecting we skip entire process
            $this->logger->error(
                \sprintf(
                    'Unable to collect data for provider %s, error: %s',
                    $provider,
                    $exception->getMessage()
                ),
                ['exception' => $exception]
            );
        }
    }

    /**
     * Process data
     *
     * @param string $fieldName
     * @param array $arguments
     * @param null|array $proxyData - added for backward compatibility with originally `processWithCallback` is called
     * @return array
     */
    public function process(string $fieldName, array $arguments = [], ?array $proxyData = null) : array
    {
        if ($proxyData !== null) {
            return $proxyData;
        }
        try {
            $info = $this->infoAssembler->assembleFieldInfo($fieldName, $this->rootProfileName);
            $snapshots = $this->extractor->extract($info, $arguments);
            return $this->transformer->transform($info, $snapshots);
        } catch (\Throwable $exception) {
            $provider = empty($info) === false ? $info->getRootNode()->getField()['provider'] : '';
            // if error happened during data collecting we skip entire process
            $this->logger->error(
                \sprintf(
                    'Unable to collect data for provider %s, error: %s',
                    $provider,
                    $exception->getMessage()
                ),
                ['exception' => $exception]
            );
        }

        return [];
    }
}
