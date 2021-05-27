<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurationDataExporter\Console\Command;

use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\ConfigurationDataExporter\Model\Whitelist\EnvironmentProvider;
use Magento\Framework\Config\File\ConfigFilePool;

/**
 * Command provides possibility to add configuration paths to whitelist in env.php
 */
class AddPathToWhitelist extends \Symfony\Component\Console\Command\Command
{
    const COMMAND_NAME = 'commerce-data-export:config:add-paths-to-whitelist';
    const INPUT_OPTION_PATHS = 'paths';

    /**
     * @var \Magento\Framework\App\DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @var \Magento\Framework\App\DeploymentConfig\Writer
     */
    private $writer;

    /**
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     * @param \Magento\Framework\App\DeploymentConfig\Writer $writer
     */
    public function __construct(
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \Magento\Framework\App\DeploymentConfig\Writer $writer
    ) {
        parent::__construct();
        $this->deploymentConfig = $deploymentConfig;
        $this->writer = $writer;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->addArgument(
            self::INPUT_OPTION_PATHS,
            InputArgument::IS_ARRAY,
            'Space-separated list of configuration paths to whitelist'
        );
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Add configuration paths to export whitelist');

        parent::configure();
    }

    /**
     * Add configuration paths to whitelist
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $paths = $input->getArgument(self::INPUT_OPTION_PATHS);

        if (empty($paths)) {
            $output->writeln('<error>No configuration path provided</error>');
            return Cli::RETURN_FAILURE;
        }

        try {
            $whitelist = $this->preparePathToSave($paths);
            $this->writer->saveConfig($whitelist, true);
        } catch (\Throwable $e) {
            $output->writeln(sprintf('<error>No configuration path provided: %s</error>', $e->getMessage()));
            return Cli::RETURN_FAILURE;
        }

        $output->writeln('<info>Paths successfully added to configuration whitelist</info>');
        return Cli::RETURN_SUCCESS;
    }

    /**
     * Prepare whitelist path to save in deployment config file.
     *
     * @param array $paths
     * @return \array[][][]
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\RuntimeException
     */
    private function preparePathToSave(array $paths): array
    {
        $whitelist = $this->deploymentConfig->get(EnvironmentProvider::WHITELIST_CONFIG_KEY, []);
        $paths = array_unique($paths);
        $paths = array_filter($paths, function ($path) use ($whitelist) {
            return !in_array($path, $whitelist);
        });

        return [
            ConfigFilePool::APP_ENV => [
                'commerce-data-export' => [
                    'configuration' => [
                        'path-whitelist' => array_merge($whitelist, $paths)
                    ]
                ]
            ]
        ];
    }
}
