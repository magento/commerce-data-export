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
declare(strict_types=1);

namespace Magento\DataExporter\Config;

use Magento\Framework\Config\DataInterface;

/**
 * Config of ReportXml
 */
class Config implements ConfigInterface
{
    /**
     * @var DataInterface
     */
    private $data;

    private static $scalars = [
        'ID', 'Int', 'Float', 'String', 'Boolean'
    ];

    /**
     * @param DataInterface $data
     */
    public function __construct(
        DataInterface $data
    ) {
        $this->data = $data;
    }

    /**
     * @inheritDoc
     */
    public function get(string $profileName) : array
    {
        $config = $this->data->get($profileName);
        if (!$config) {
            throw new \RuntimeException(\sprintf('et_schema error: no config found for entity "%s"', $profileName));
        }
        return $config;
    }

    /**
     * @inheritDoc
     */
    public function isScalar(string $typeName): bool
    {
        return in_array($typeName, self::$scalars);
    }
}
