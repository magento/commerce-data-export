<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Indexer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Mview\View\ChangeLogBatchWalkerInterface;
use Magento\Framework\Mview\View\ChangelogInterface;
use Magento\Framework\Mview\View\ChangelogTableNotExistsException;

class ChangeLogBatchWalker implements ChangeLogBatchWalkerInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var string[]
     */
    private $priceAttributes;

    /**
     * @param ResourceConnection $resourceConnection
     * @param string[] $priceAttributes
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        array $priceAttributes = ['price', 'special_price']
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->priceAttributes = $priceAttributes;
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function walk(ChangelogInterface $changelog, int $fromVersionId, int $toVersion, int $batchSize)
    {
        $connection = $this->resourceConnection->getConnection();
        $changelogTableName = $this->resourceConnection->getTableName($changelog->getName());

        if (!$connection->isTableExists($changelogTableName)) {
            throw new ChangelogTableNotExistsException(__('Table %1 does not exist', [$changelogTableName]));
        }

        // TODO handle columns in "more dynamic way"
        $select = $connection->select()
            ->from(['cl' => $changelogTableName], [])
            ->joinLeft(
                ['eav' => $this->resourceConnection->getTableName('eav_attribute')],
                'cl.attribute_id = eav.attribute_id',
                []
            )
            ->where('version_id > ?', $fromVersionId)
            ->where('version_id <= ?', $toVersion)
            ->where(\implode(' OR ', [
                $connection->quoteInto('eav.attribute_code IN (?)', $this->priceAttributes),
                'eav.attribute_code IS NULL'
            ]))
            ->group(
                [
                    'cl.' . $changelog->getColumnName(),
                    'cl.scope_id',
                    'cl.price_type',
                    'cl.all_groups',
                    'cl.customer_group_id',
                    'cl.qty',
                    'cl.variation_id',
                ]
            )
            ->columns(
                [
                    'entity_id' => 'cl.' . $changelog->getColumnName(),
                    'attributes' => new Expression('GROUP_CONCAT(eav.attribute_code)'),
                    'scope_id' => 'cl.scope_id',
                    'price_type' => 'cl.price_type',
                    'all_groups' => 'cl.all_groups',
                    'customer_group_id' => 'cl.customer_group_id',
                    'qty' => 'cl.qty',
                    'variation_id' => 'cl.variation_id',
                ]
            )
            ->limit($batchSize);

        return $connection->fetchAll($select);
    }
}
