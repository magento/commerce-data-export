<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 */

namespace Magento\QueryXml\Model\Config;

/**
 * Interface ConfigInterface
 *
 * Interface for ReportXml Config
 */
interface ConfigInterface
{
    /**
     * Config of ReportXml
     *
     * @param string $queryName
     * @return array
     */
    public function get(string $queryName): array;
}
