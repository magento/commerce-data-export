<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 */

namespace Magento\QueryXml\Model\Config;

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

    /**
     * Config constructor.
     *
     * @param DataInterface $data
     */
    public function __construct(
        DataInterface $data
    ) {
        $this->data = $data;
    }

    /**
     * Returns config value by name
     *
     * @param string $queryName
     * @return array
     */
    public function get(string $queryName): array
    {
        return $this->data->get($queryName);
    }
}
