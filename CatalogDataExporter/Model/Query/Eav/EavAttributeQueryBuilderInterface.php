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

namespace Magento\CatalogDataExporter\Model\Query\Eav;

use Magento\Framework\DB\Select;

/**
 * Build Select object to fetch eav attributes for provided entity type
 */
interface EavAttributeQueryBuilderInterface
{
    /**
     * Form and return query to get eav entity $attributes for given $entityIds.
     *
     * If eav entities were not found, then data is fetching from $entityTableName.
     *
     * @param int[] $entityIds
     * @param array $attributes
     * @param string $storeCode
     *
     * @return Select
     *
     * @throws \Exception
     */
    public function build(array $entityIds, array $attributes, string $storeCode): Select;
}
