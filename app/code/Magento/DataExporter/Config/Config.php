<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
