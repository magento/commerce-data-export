<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Indexer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Mview\View\ChangeLogBatchWalkerInterface;
use Magento\Framework\Mview\View\ChangelogInterface;
use Magento\Framework\Mview\View\ChangelogTableNotExistsException;

/**
 * Changelog table batch walker
 */
class GiftCardChangeLogBatchWalker implements ChangeLogBatchWalkerInterface
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
            ->join(
                ['eav' => $this->resourceConnection->getTableName('eav_attribute')],
                'cl.attribute_id = eav.attribute_id',
                []
            )
            ->join(
                ['cpe' => $productEntityTable = $this->resourceConnection->getTableName('catalog_product_entity')],
                \sprintf(
                    'cpe.%s = cl.%s',
                    $connection->getAutoIncrementField($productEntityTable),
                    $changelog->getColumnName()
                ),
                []
            )
            ->where('cl.version_id > ?', $fromVersionId)
            ->where('cl.version_id <= ?', $toVersion)
            ->group(
                [
                    'cl.' . $changelog->getColumnName(),
                    'cl.scope_id',
                    'cl.attribute_id',
                    'cl.value',
                ]
            )
            ->columns(
                [
                    'entity_id' => 'cpe.entity_id',
                    'attribute' => 'eav.attribute_code',
                    'scope_id' => 'cl.scope_id',
                    'value' => 'cl.value',
                    'price_type' => 'cl.price_type',
                ]
            )
            ->limit($batchSize);

        return $connection->fetchAll($select);
    }
}
