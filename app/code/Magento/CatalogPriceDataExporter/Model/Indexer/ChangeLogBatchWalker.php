<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Indexer;

use Magento\CatalogPriceDataExporter\Model\Provider\FullReindex\ProductPriceEvent;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Mview\View\ChangeLogBatchWalkerInterface;
use Magento\Framework\Mview\View\ChangelogInterface;
use Magento\Framework\Mview\View\ChangelogTableNotExistsException;

/**
 * Changelog table batch walker
 */
class ChangeLogBatchWalker implements ChangeLogBatchWalkerInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function walk(ChangelogInterface $changelog, int $fromVersionId, int $toVersion, int $batchSize): array
    {
        $connection = $this->resourceConnection->getConnection();
        $changelogTableName = $this->resourceConnection->getTableName($changelog->getName());

        if (!$connection->isTableExists($changelogTableName)) {
            throw new ChangelogTableNotExistsException(__('Table %1 does not exist', [$changelogTableName]));
        }

        $select = $connection->select()
            ->from(['cl' => $changelogTableName], [])
            ->joinLeft(
                ['eav' => $this->resourceConnection->getTableName('eav_attribute')],
                'cl.attribute_id = eav.attribute_id',
                []
            )
            ->joinLeft(
                ['cpe' => $productEntityTable = $this->resourceConnection->getTableName('catalog_product_entity')],
                \sprintf('cpe.%s = cl.parent_id', $connection->getAutoIncrementField($productEntityTable)),
                []
            )
            ->where('cl.version_id > ?', $fromVersionId)
            ->where('cl.version_id <= ?', $toVersion)
            ->where('cl.entity_id != 0')
            ->where(\implode(' OR ', [
                $connection->quoteInto('eav.attribute_code IN (?)', ProductPriceEvent::PRICE_ATTRIBUTES),
                'eav.attribute_code IS NULL'
            ]))
            ->group(
                [
                    'cl.' . $changelog->getColumnName(),
                    'cl.scope_id',
                    'cl.price_type',
                    'cl.qty',
                    'cl.option_id',
                    'customer_group',
                    'cl.parent_id'
                ]
            )
            ->columns(
                [
                    'entity_id' => 'cl.' . $changelog->getColumnName(),
                    'attributes' => new Expression('GROUP_CONCAT(eav.attribute_code)'),
                    'scope_id' => 'cl.scope_id',
                    'price_type' => 'cl.price_type',
                    'customer_group' => new Expression(
                        'CASE WHEN cl.all_groups = 1 THEN NULL ELSE cl.customer_group_id END'
                    ),
                    'qty' => 'cl.qty',
                    'cl.option_id',
                    'parent_id' => 'cpe.entity_id',
                ]
            )
            ->limit($batchSize);

        return $connection->fetchAll($select);
    }
}
