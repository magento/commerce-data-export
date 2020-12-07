<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurationDataExporter\Test\Integration\ConfigExport;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class ConfigurationExportTest extends TestCase
{
    const CONNECTION_AMPQ = 'amqp';
    const QUEUE_NAME = 'system.configuration.export.queue';
    const TOPIC_NAME = 'system.configuration.export';
    const TEST_CONFIG_PATH = 'section/group/field';
    const TEST_CONFIG_VALUE = 'test value';
    const TEST_CONFIG_SCOPE = 'stores';
    const TEST_CONFIG_SCOPE_ID = 1;

    /**
     * @var  \Magento\Config\Model\Config\Factory
     */
    private $configValueFactory;

    /**
     * @var \Magento\Framework\MessageQueue\QueueRepository
     */
    private $queueRepository;

    /**
     * @var \Magento\Framework\MessageQueue\MessageEncoder
     */
    private $messageEncoder;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $objectManager = Bootstrap::getObjectManager();
        $this->configValueFactory = $objectManager->create(\Magento\Config\Model\Config\Factory::class);
        $this->queueRepository = $objectManager->create(\Magento\Framework\MessageQueue\QueueRepository::class);
        $this->messageEncoder = $objectManager->create(\Magento\Framework\MessageQueue\MessageEncoder::class);
    }

    /**
     * @param array $configs
     * @param array $expected
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @dataProvider configExportDataProvider
     * @magentoDbIsolation enabled
     */
    public function testConfigUpdateExport(array $configs, array $expected): void
    {
        // to avoid consuming of incorrect message we need to purge queue before running test
        /** @var \Magento\Framework\Amqp\Config $amqpConfig */
        $amqpConfig = Bootstrap::getObjectManager()->get(\Magento\Framework\Amqp\Config::class);
        $amqpConfig->getChannel()->queue_purge(self::QUEUE_NAME);

        $whitelist = $expected['whitelist']??[];

        // configure whitelist before running test
        Bootstrap::getObjectManager()->configure([
            \Magento\ConfigurationDataExporter\Model\Whitelist\ModularProvider::class => [
                'arguments' => [
                    'whitelist' => $whitelist
                ],
            ],
        ]);

        // add export observer to config_data_save_after event
        // (config_data_save_commit_after not available on integration tests)
        /** @var \Magento\Framework\Event\Config\Data $eventConfig */
        $eventConfig = Bootstrap::getObjectManager()->get(\Magento\Framework\Event\Config\Data::class);
        $eventConfig->merge([
            'config_data_save_after' => [
                'export_config_change' => [
                    'name' => 'export_config_change',
                    'instance' => 'Magento\ConfigurationDataExporter\Observer\ConfigChange'
                ]
            ]
        ]);

        $config = $this->configValueFactory->create();

        foreach ($configs as $item) {
            $config->setScope($item['scope']);
            $config->setScopeId($item['scope_id']);
            $config->setDataByPath($item['path'], $item['value']);
        }

        $config->save();

        /** @var \Magento\ConfigurationDataExporter\Api\WhitelistProviderInterface $whitelistPool */
        $whitelistPool = Bootstrap::getObjectManager()
            ->get(\Magento\ConfigurationDataExporter\Api\WhitelistProviderInterface::class);

        self::assertTrue(in_array(self::TEST_CONFIG_PATH, $whitelistPool->getWhitelist()));

        /** @var \Magento\ConfigurationDataExporter\Api\ConfigRegistryself::ce $configRegistry */
        $configRegistry = Bootstrap::getObjectManager()
            ->get(\Magento\ConfigurationDataExporter\Api\ConfigRegistryInterface::class);

        self::assertEquals($expected['collected_paths'], count($configRegistry->getValues()));

        // just to be sure that message was already published i queue
        sleep(1);

        $queue = $this->queueRepository->get(self::CONNECTION_AMPQ, self::QUEUE_NAME);
        $msg = $queue->dequeue();
        $queue->acknowledge($msg);

        $changedConfig = $this->messageEncoder->decode(self::TOPIC_NAME, $msg->getBody());

        self::assertInstanceOf(
            \Magento\ConfigurationDataExporter\Event\Data\ChangedConfig::class,
            $changedConfig
        );

        $meta = $changedConfig->getMeta();
        $exportedConfigs = $changedConfig->getData()->getConfig();
        $actual = [];

        foreach ($exportedConfigs as $item) {
            $actual[] = [
                'event' => $meta->getEvent(),
                'store' => $item->getStore(),
                'name' => $item->getName(),
                'value' => $item->getValue()
            ];
        }

        self::assertEquals($expected['exported_paths'], count($exportedConfigs));
        self::assertEquals($expected['result'], $actual);
    }

    /**
     * @return array
     */
    public function configExportDataProvider(): array
    {
        return [
            [
                [
                    [
                        'path' => self::TEST_CONFIG_PATH,
                        'value' => self::TEST_CONFIG_VALUE,
                        'scope' => self::TEST_CONFIG_SCOPE,
                        'scope_id' => self::TEST_CONFIG_SCOPE_ID
                    ],
                    [
                        'path' => 'section/group/not_exported_field',
                        'value' => 'not allowed value',
                        'scope' => 'stores',
                        'scope_id' => 1
                    ]
                ],
                [
                    'whitelist' => [self::TEST_CONFIG_PATH],
                    'collected_paths' => 2,
                    'exported_paths' => 1,
                    'result' => [
                        [
                            'event' => 'config_export_update',
                            'store' => self::TEST_CONFIG_SCOPE_ID,
                            'name' => self::TEST_CONFIG_PATH,
                            'value' => self::TEST_CONFIG_VALUE
                        ]
                    ]
                ]
            ]
        ];
    }
}
