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

namespace Magento\CatalogDataExporter\Model\Resolver;

use Magento\Framework\App\ResourceConnection;

/**
 * Class responsible for resolving attribute ids
 */
class AttributesResolver
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Resolve attribute ids. Return attribute codes
     *
     * @param int[] $ids
     *
     * @return string[]
     */
    public function resolve(array $ids): array
    {
        $connection = $this->resourceConnection->getConnection();

        return $connection->fetchCol(
            $connection->select()
                ->from(['eav' => $this->resourceConnection->getTableName('eav_attribute')], ['attribute_code'])
                ->where('eav.attribute_id IN (?)', $ids)
        );
    }
}
