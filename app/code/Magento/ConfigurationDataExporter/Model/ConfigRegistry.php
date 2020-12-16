<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurationDataExporter\Model;

/**
 * Accumulate changed config values for export
 */
class ConfigRegistry implements \Magento\ConfigurationDataExporter\Api\ConfigRegistryInterface
{
    /**
     * @var array
     */
    private $values = [];

    /**
     * @inheritDoc
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @inheritDoc
     */
    public function addValue(array $value = []): void
    {
        if (!empty($value) && !in_array($value, $this->values)) {
            $this->values[] = $value;
        }
    }

    /**
     * @inheritDoc
     */
    public function isEmpty(): bool
    {
        return count($this->values) === 0;
    }
}
