<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
