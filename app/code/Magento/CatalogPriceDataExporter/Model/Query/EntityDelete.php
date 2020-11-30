<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\Expression;

class EntityDelete
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var string
     */
    private $tableName;

    /**
     * @var string
     */
    private $entityColumnName;

    /**
     * @param ResourceConnection $resourceConnection
     * @param string $tableName
     * @param string $entityColumnName
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        string $tableName = '',
        string $entityColumnName = ''
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->tableName = $tableName;
        $this->entityColumnName = $entityColumnName;
    }

    /**
     * Retrieve query for deleted entity.
     *
     * @param string $optionId
     *
     * @return Select
     */
    public function getQuery(string $optionId): Select
    {
        $connection = $this->resourceConnection->getConnection();

        return $connection->select()
            ->from($this->resourceConnection->getTableName($this->tableName), [])
            ->columns(
                [
                    'is_active' => new Expression(1),
                ]
            )
            ->where(\sprintf('%s = ?', $this->entityColumnName), $optionId);
    }
}
