<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
