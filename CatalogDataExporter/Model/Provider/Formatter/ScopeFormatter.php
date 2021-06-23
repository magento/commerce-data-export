<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Formatter;

use Magento\Framework\App\ResourceConnection;

/**
 * Scope formatter for product provider
 */
class ScopeFormatter implements FormatterInterface
{
    /**
     * @var array
     */
    private $scopes;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * ScopeFormatter constructor.
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get resource table
     *
     * @param string $tableName
     * @return string
     */
    private function getTable(string $tableName) : string
    {
        return $this->resourceConnection->getTableName($tableName);
    }

    /**
     * Get scopes
     *
     * @param bool $resetCache
     * @return array
     */
    private function getScopes($resetCache = false): array
    {
        if ($resetCache) {
            $this->scopes = [];
        }
        if (!$this->scopes) {
            $connection = $this->resourceConnection->getConnection();
            $select = $connection->select()
                ->from(['s' => $this->getTable('store')], [])
                ->join(
                    ['g' => $this->getTable('store_group')],
                    'g.group_id = s.group_id',
                    []
                )
                ->join(
                    ['w' => $this->getTable('store_website')],
                    'w.website_id = s.website_id',
                    []
                )
                ->columns(
                    [
                        'storeViewCode' => 's.code',
                        'storeCode' => 'g.code',
                        'websiteCode' => 'w.code'
                    ]
                )
                ->where('s.store_id != 0');
            $this->scopes = $connection->fetchAssoc($select);
        }
        return $this->scopes;
    }

    /**
     * Format data
     *
     * @param array $row
     * @return array
     */
    public function format(array $row): array
    {
        $scopes = $this->getScopes();
        if (!isset($scopes[$row['storeViewCode']])) {
            $scopes = $this->getScopes(true);
        }
        $scope = $scopes[$row['storeViewCode']] ?? null;

        return $scope ? array_merge($row, $scope) : $row;
    }
}
