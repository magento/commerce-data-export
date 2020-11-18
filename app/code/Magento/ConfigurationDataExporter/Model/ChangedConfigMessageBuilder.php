<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurationDataExporter\Model;

use Magento\ConfigurationDataExporter\Event\Data\ChangedConfig;
use Magento\ConfigurationDataExporter\Event\Data\ChangedConfigFactory;
use Magento\ConfigurationDataExporter\Event\Data\MetaFactory;
use Magento\ConfigurationDataExporter\Event\Data\ConfigFactory;
use Magento\ConfigurationDataExporter\Event\Data\DataFactory;

/**
 * Class that builds queue message for changed config
 */
class ChangedConfigMessageBuilder
{
    /**
     * @var ChangedConfigFactory
     */
    private $changedConfigFactory;

    /**
     * @var MetaFactory
     */
    private $metaFactory;

    /**
     * @var DataFactory
     */
    private $dataFactory;

    /**
     * @var ConfigFactory
     */
    private $configFactory;

    /**
     * @param ChangedConfigFactory $changedConfigFactory
     * @param MetaFactory $metaFactory
     * @param DataFactory $dataFactory
     * @param ConfigFactory $configFactory
     */
    public function __construct(
        ChangedConfigFactory $changedConfigFactory,
        MetaFactory $metaFactory,
        DataFactory $dataFactory,
        ConfigFactory $configFactory
    ) {
        $this->changedConfigFactory = $changedConfigFactory;
        $this->metaFactory = $metaFactory;
        $this->dataFactory = $dataFactory;
        $this->configFactory = $configFactory;
    }

    /**
     * Build message object
     *
     * @param string $eventType
     * @param array $configData
     *
     * @return ChangedConfig
     */
    public function build(string $eventType, array $configData): ChangedConfig
    {
        $meta = $this->metaFactory->create();
        $meta->setEventType($eventType);
        $configArray = [];

        foreach ($configData as $item) {
            $config = $this->configFactory->create();
            $config->setStore((int)$item['scope_id']);
            $config->setName((string)$item['path']);
            $config->setValue($item['value']);

            $configArray[] = $config;
        }

        $data = $this->dataFactory->create();
        $data->setConfig($configArray);

        return $this->changedConfigFactory->create(
            [
                'meta' => $meta,
                'data' => $data
            ]
        );
    }
}
