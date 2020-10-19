<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

use Magento\Framework\App\ResourceConnection;

/**
 * Action responsible for marking entities as removed
 */
class MarkRemovedEntities implements MarkRemovedEntitiesInterface
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
     * @inheritdoc
     */
    public function execute(array $ids, FeedIndexMetadata $metadata): void
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->joinLeft(
                ['s' => $this->resourceConnection->getTableName($metadata->getSourceTableName())],
                \sprintf('f.%s = s.%s', $metadata->getFeedTableField(), $metadata->getSourceTableField()),
                ['is_deleted' => new \Zend_Db_Expr('1')]
            )
            ->where(\sprintf('f.%s IN (?)', $metadata->getFeedTableField()), $ids);

        if ($metadata->getScopeTableName()) {
            $select
                ->join(
                    ['st' => $this->resourceConnection->getTableName('store')],
                    'f.store_view_code = st.code',
                    []
                )
                ->joinLeft(
                    ['sc' => $this->resourceConnection->getTableName($metadata->getScopeTableName())],
                    \sprintf(
                        'sc.%s = s.%s AND sc.%3$s = st.%3$s',
                        $metadata->getScopeTableField(),
                        $metadata->getSourceTableField(),
                        $metadata->getScopeCode(),
                    ),
                    []
                )
                ->where(\sprintf('sc.%s IS NULL', $metadata->getScopeTableField()));
        } else {
            $select->where(\sprintf('s.%s IS NULL', $metadata->getSourceTableField()));
        }

        $update = $connection->updateFromSelect(
            $select,
            ['f' => $this->resourceConnection->getTableName($metadata->getFeedTableName())]
        );
        $connection->query($update);
    }
}
