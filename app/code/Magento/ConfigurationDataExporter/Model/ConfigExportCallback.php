<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurationDataExporter\Model;

use Magento\Framework\MessageQueue\PublisherInterface;
use Psr\Log\LoggerInterface;

/**
 * Publishes data of updated config data in queue
 */
class ConfigExportCallback implements ConfigExportCallbackInterface
{
    /**
     * @var PublisherInterface
     */
    private $queuePublisher;

    /**
     * @var ChangedConfigMessageBuilder
     */
    private $messageBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $topicName;

    /**
     * @var int
     */
    private $batchSize;

    /**
     * @param PublisherInterface $queuePublisher
     * @param ChangedConfigMessageBuilder $messageBuilder
     * @param LoggerInterface $logger
     * @param string $topicName
     * @param int $batchSize
     */
    public function __construct(
        PublisherInterface $queuePublisher,
        ChangedConfigMessageBuilder $messageBuilder,
        LoggerInterface $logger,
        string $topicName,
        int $batchSize = 100
    ) {
        $this->queuePublisher = $queuePublisher;
        $this->messageBuilder = $messageBuilder;
        $this->logger = $logger;
        $this->topicName = $topicName;
        $this->batchSize = $batchSize;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $evenType, array $configData = []) : void
    {
        foreach (\array_chunk($configData, $this->batchSize) as $chunk) {
            $this->publishMessage($chunk, $evenType);
        }
    }

    /**
     * Publish config updates message
     *
     * @param array $configData
     * @param string $evenType
     * @return void
     */
    private function publishMessage(array $configData, string $evenType): void
    {
        $message = $this->messageBuilder->build($evenType, $configData);
        $configToPublish = $message->getData() ? $message->getData()->getConfig() : [];

        if (!empty($configToPublish)) {
            try {
                $this->queuePublisher->publish($this->topicName, $message);
            } catch (\Throwable $e) {
                $this->logger->error(
                    sprintf(
                        'topic "%s": error on publish message "%s"',
                        $this->topicName,
                        \json_encode(['event_type' => $evenType])
                    ),
                    ['exception' => $e]
                );
            }
        }
    }
}
