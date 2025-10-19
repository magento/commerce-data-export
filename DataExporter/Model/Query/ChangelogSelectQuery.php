<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Mview\View\Changelog;

/**
 * Default query to get change log select
 */
class ChangelogSelectQuery implements ChangelogSelectQueryInterface
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;
    public function __construct(ResourceConnection  $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param string $sourceTableName
     * @param string $sourceTableField
     * @param int $lastVersionId
     *
     * @return Select
     */
    public function getChangelogSelect(
        string $sourceTableName,
        string $sourceTableField,
        int $lastVersionId
    ): Select {
       return $this->resourceConnection->getConnection()
            ->select()
            ->from(
                ['v' => $this->resourceConnection->getTableName($sourceTableName)],
                [$sourceTableField]
            )
            ->distinct(true)
            ->where(sprintf('v.%s > ?', Changelog::VERSION_ID_COLUMN_NAME), $lastVersionId);
    }
}
