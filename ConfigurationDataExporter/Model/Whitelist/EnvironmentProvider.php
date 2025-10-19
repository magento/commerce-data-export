<?php
/**
 * Copyright 2022 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\ConfigurationDataExporter\Model\Whitelist;

use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * Whitelist defined in deployment configuration files
 */
class EnvironmentProvider implements \Magento\ConfigurationDataExporter\Api\WhitelistProviderInterface
{
    const WHITELIST_CONFIG_KEY = 'commerce-data-export/configuration/path-whitelist';

    /**
     * @var array
     */
    private $whitelist;

    /**
     * @var \Magento\Framework\App\DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        LoggerInterface $logger
    ) {
        $this->deploymentConfig = $deploymentConfig;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function getWhitelist(): array
    {
        if (empty($this->whitelist)) {
            try {
                $this->whitelist = $this->deploymentConfig->get(self::WHITELIST_CONFIG_KEY, []);
            } catch (\Throwable $e) {
                $this->logger->error('Cannot read path whitelist from deployment configuration ', [$e->getMessage()]);
            }
        }

        return $this->whitelist;
    }
}
