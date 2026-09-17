<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Eav;

use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Model\AbstractModel;

/**
 * Detects attribute option label changes during attribute save and schedules a feed update
 * for entities carrying the changed (option, store) pairs.
 *
 * Entity specifics (entity type code, entity interface, main table, feed indexer id) are
 * injected via DI. Configured for products and categories via separate virtual types
 * (see di.xml); the concrete `Plugin\Eav\Resync*OnAttributeOptionLabelChange` plugins each
 * inject the matching configured instance and call {@see beforeSave()}/{@see afterSave()}
 * around Attribute::save().
 *
 * TODO: move affected-entities threshold to env config to be able configure for edge case
 */
class AttributeOptionLabelChangeResync
{
    private const AFFECTED_ENTITIES_THRESHOLD = 1000;

    private const FRONTEND_INPUT_SELECT = 'select';
    private const FRONTEND_INPUT_MULTISELECT = 'multiselect';
    private const SUPPORTED_FRONTEND_INPUTS = [self::FRONTEND_INPUT_SELECT, self::FRONTEND_INPUT_MULTISELECT];

    /**
     * Backend types that map to a real per-store (attribute_id, store_id, value) EAV table.
     *
     * Mirrors {@see \Magento\CatalogDataExporter\Model\Query\Eav\EavAttributeQueryBuilder}.
     * Attributes outside this list (e.g. `static`) have no such table - `getBackendTable()`
     * falls back to the entity table itself, which would join incorrectly.
     */
    private const SUPPORTED_BACKEND_TYPES = ['int', 'decimal', 'text', 'varchar', 'datetime'];

    private ?int $targetEntityTypeId = null;

    /**
     * @param ResourceConnection $resourceConnection
     * @param EavConfig $eavConfig
     * @param IndexerRegistry $indexerRegistry
     * @param MetadataPool $metadataPool
     * @param CommerceDataExportLoggerInterface $logger
     * @param string $entityTypeCode
     * @param string $entityInterface
     * @param string $mainTable
     * @param string $feedIndexerId
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly EavConfig $eavConfig,
        private readonly IndexerRegistry $indexerRegistry,
        private readonly MetadataPool $metadataPool,
        private readonly CommerceDataExportLoggerInterface $logger,
        private readonly string $entityTypeCode,
        private readonly string $entityInterface,
        private readonly string $mainTable,
        private readonly string $feedIndexerId
    ) {
    }

    /**
     * Snapshot option labels before attribute save runs, if this save is in scope.
     *
     * @param AbstractModel $object
     * @return array|null nested array keyed by (option_id, store_id) with values as labels,
     *                     or null if this attribute save is out of scope
     */
    public function beforeSave(AbstractModel $object): ?array
    {
        if (!$this->shouldHandle($object)) {
            return null;
        }
        try {
            return $this->loadOptionLabels((int) $object->getAttributeId());
        } catch (\Throwable $e) {
            $this->logError($object, $e);
            return null;
        }
    }

    /**
     * Diff option labels after attribute save runs.
     *
     * Schedules a feed update for entities carrying any changed (option_id, store_id) pair.
     *
     * @param AbstractModel $object
     * @param array|null $oldLabels snapshot returned by {@see beforeSave()}
     * @return void
     */
    public function afterSave(AbstractModel $object, ?array $oldLabels): void
    {
        if ($oldLabels === null) {
            return;
        }
        try {
            [$changedOptionIds, $changedStoreIds] = $this->detectChanges(
                $oldLabels,
                $this->loadOptionLabels((int) $object->getAttributeId())
            );
            $this->scheduleFeedUpdate($object, $changedOptionIds, $changedStoreIds);
        } catch (\Throwable $e) {
            $this->logError($object, $e);
        }
    }

    /**
     * Whether this attribute save is in scope for feed scheduling.
     *
     * @param AbstractModel $object
     *
     * @return bool
     */
    private function shouldHandle(AbstractModel $object): bool
    {
        if ($object->isObjectNew()) {
            return false;
        }
        if (!\in_array((string) $object->getFrontendInput(), self::SUPPORTED_FRONTEND_INPUTS, true)) {
            return false;
        }
        return $this->isTargetEntityAttribute($object);
    }

    /**
     * Whether the attribute belongs to the configured target entity type.
     *
     * @param AbstractModel $object
     *
     * @return bool
     */
    private function isTargetEntityAttribute(AbstractModel $object): bool
    {
        $entityTypeId = (int) $object->getEntityTypeId();
        if ($entityTypeId <= 0) {
            return false;
        }
        if ($this->targetEntityTypeId === null) {
            try {
                $this->targetEntityTypeId = (int) $this->eavConfig->getEntityType($this->entityTypeCode)->getId();
            } catch (\Throwable) {
                return false;
            }
        }
        return $entityTypeId === $this->targetEntityTypeId;
    }

    /**
     * Load all option labels for the given attribute.
     *
     * Reads from eav_attribute_option_value instead of $object->getOptions() to prevent getting
     * cached results. Returns a nested array keyed by (option_id, store_id) with values as labels.
     *
     * @param int $attributeId
     *
     * @return array nested array keyed by (option_id, store_id) with values as labels
     */
    private function loadOptionLabels(int $attributeId): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                ['o' => $this->resourceConnection->getTableName('eav_attribute_option')],
                []
            )
            ->joinInner(
                ['v' => $this->resourceConnection->getTableName('eav_attribute_option_value')],
                'v.option_id = o.option_id',
                ['option_id', 'store_id', 'value']
            )
            ->where('o.attribute_id = ?', $attributeId);

        $rows = $connection->fetchAll($select);
        $labels = [];
        foreach ($rows as $row) {
            $labels[(int) $row['option_id']][(int) $row['store_id']] = (string) $row['value'];
        }
        return $labels;
    }

    /**
     * Detect changes between old and new labels.
     *
     * @param array $oldLabels nested array keyed by (option_id, store_id) with values as labels
     * @param array $newLabels nested array keyed by (option_id, store_id) with values as labels
     *
     * @return array [changedOptionIds, changedStoreIds]
     */
    private function detectChanges(array $oldLabels, array $newLabels): array
    {
        $changedOptionIds = [];
        $changedStoreIdGroups = [];

        foreach ($oldLabels as $optionId => $optionValues) {
            $changedOrAdded = array_diff($newLabels[$optionId] ?? [], $optionValues);
            $deletedOrAdded = array_diff($optionValues, $newLabels[$optionId] ?? []);
            if (!empty($deletedOrAdded) || !empty($changedOrAdded)) {
                $changedOptionIds[] = $optionId;
                $changedStoreIdGroups[] = array_keys($changedOrAdded);
                $changedStoreIdGroups[] = array_keys($deletedOrAdded);
            }
        }
        $changedStoreIds = empty($changedStoreIdGroups) ? [] : array_merge(...$changedStoreIdGroups);
        return [array_unique($changedOptionIds), array_unique($changedStoreIds)];
    }

    /**
     * Schedule feed update based on number of affected entities.
     *
     * Either invalidate the feed indexer or insert affected entity ids into the changelog,
     * depending on how many entities carry any of the changed option ids.
     *
     * @param AbstractModel $attribute
     * @param int[] $changedOptionIds
     * @param int[] $changedStoreIds
     *
     * @return void
     */
    private function scheduleFeedUpdate(
        AbstractModel $attribute,
        array $changedOptionIds,
        array $changedStoreIds
    ): void {
        if (empty($changedOptionIds)) {
            return;
        }

        $backendType = (string) $attribute->getBackendType();
        if (!\in_array($backendType, self::SUPPORTED_BACKEND_TYPES, true)) {
            return;
        }
        $backendTable = \sprintf('%s_%s', $this->mainTable, $backendType);

        // Always include admin (0) - the feed falls back to the admin row when a store override is absent.
        $changedStoreIds[] = 0;
        $storeIds = array_unique($changedStoreIds);

        $indexer = $this->indexerRegistry->get($this->feedIndexerId);

        if (!$indexer->getView()->isEnabled()) {
            $indexer->invalidate();
            $this->logger->info(
                'Attribute option label change detected. '
                . 'Full resync scheduled due to feed is not in schedule update mode'
            );
            return;
        }

        $linkField = $this->metadataPool->getMetadata($this->entityInterface)->getLinkField();
        $affectedEntitiesSelect = $this->buildAffectedEntitiesSelect(
            $attribute,
            $backendTable,
            $linkField,
            $changedOptionIds,
            $storeIds
        );
        $affectedEntityCount = $this->countAffectedEntities($affectedEntitiesSelect);
        if ($affectedEntityCount <= 0) {
            return;
        }

        if ($affectedEntityCount > self::AFFECTED_ENTITIES_THRESHOLD) {
            $indexer->invalidate();
        } else {
            $this->addEntityIdsToChangelog(
                $affectedEntitiesSelect,
                (string) $indexer->getView()->getChangelog()->getName()
            );
        }

        $this->logger->info(sprintf(
            'Attribute option label change detected. Attribute id: %d, affected entities: %d. %s.',
            (int) $attribute->getAttributeId(),
            $affectedEntityCount,
            $affectedEntityCount > self::AFFECTED_ENTITIES_THRESHOLD
                ? 'Full resync scheduled'
                : 'Partial resync scheduled'
        ));
    }

    /**
     * Count distinct affected entities, capped at threshold + 1.
     *
     * @param \Magento\Framework\DB\Select $affectedEntitiesSelect
     *
     * @return int
     */
    private function countAffectedEntities(\Magento\Framework\DB\Select $affectedEntitiesSelect): int
    {
        $connection = $this->resourceConnection->getConnection();
        $innerSelect = (clone $affectedEntitiesSelect)->limit(self::AFFECTED_ENTITIES_THRESHOLD + 1);
        $countSelect = $connection->select()
            ->from(['t' => $innerSelect], [new Expression('COUNT(1)')]);

        return (int) $connection->fetchOne($countSelect);
    }

    /**
     * Insert distinct affected entity ids into the feed changelog (single query).
     *
     * @param \Magento\Framework\DB\Select $affectedEntitiesSelect
     * @param string $changelogTableName
     *
     * @return void
     */
    private function addEntityIdsToChangelog(
        \Magento\Framework\DB\Select $affectedEntitiesSelect,
        string $changelogTableName
    ): void {
        $connection = $this->resourceConnection->getConnection();
        $connection->query(
            $connection->insertFromSelect(
                $affectedEntitiesSelect,
                $this->resourceConnection->getTableName($changelogTableName),
                ['entity_id']
            )
        );
    }

    /**
     * Build the affected-entities select - dispatches on frontend_input.
     *
     * Select attributes (int backend) use a direct `value IN (?)` which hits the
     * (attribute_id, value) index on the main entity int table. Multiselect
     * attributes (varchar backend, comma-separated option ids) use FIND_IN_SET
     * because no index can be used against a CSV.
     *
     * @param AbstractModel $attribute
     * @param string $backendTable
     * @param string $linkField
     * @param int[] $changedOptionIds
     * @param int[] $storeIds
     *
     * @return \Magento\Framework\DB\Select
     */
    private function buildAffectedEntitiesSelect(
        AbstractModel $attribute,
        string $backendTable,
        string $linkField,
        array $changedOptionIds,
        array $storeIds
    ): \Magento\Framework\DB\Select {
        return (string) $attribute->getFrontendInput() === self::FRONTEND_INPUT_MULTISELECT
            ? $this->buildAffectedMultiselectEntitiesSelect(
                $attribute,
                $backendTable,
                $linkField,
                $changedOptionIds,
                $storeIds
            )
            : $this->buildAffectedSelectEntitiesSelect(
                $attribute,
                $backendTable,
                $linkField,
                $changedOptionIds,
                $storeIds
            );
    }

    /**
     * Build the affected-entities select for `select` attributes (int backend).
     *
     * @param AbstractModel $attribute
     * @param string $backendTable
     * @param string $linkField
     * @param int[] $changedOptionIds
     * @param int[] $storeIds
     *
     * @return \Magento\Framework\DB\Select
     */
    private function buildAffectedSelectEntitiesSelect(
        AbstractModel $attribute,
        string $backendTable,
        string $linkField,
        array $changedOptionIds,
        array $storeIds
    ): \Magento\Framework\DB\Select {
        $connection = $this->resourceConnection->getConnection();
        $joinCondition = sprintf('eav.%1$s = p.%1$s', $linkField)
            . $connection->quoteInto(' AND eav.attribute_id = ?', (int) $attribute->getAttributeId())
            . $connection->quoteInto(' AND eav.store_id IN (?)', $storeIds);

        return $connection->select()
            ->distinct()
            ->from(
                ['p' => $this->resourceConnection->getTableName($this->mainTable)],
                ['entity_id' => 'p.entity_id']
            )
            ->joinInner(
                ['eav' => $this->resourceConnection->getTableName($backendTable)],
                $joinCondition,
                []
            )
            ->where('eav.value IN (?)', $changedOptionIds);
    }

    /**
     * Build the affected-entities select for `multiselect` attributes (varchar backend, CSV value).
     *
     * Each changed option id contributes a FIND_IN_SET predicate; the predicates are OR'd.
     * Number of predicates is bounded by the number of options the admin changed in the save.
     *
     * @param AbstractModel $attribute
     * @param string $backendTable
     * @param string $linkField
     * @param int[] $changedOptionIds
     * @param int[] $storeIds
     *
     * @return \Magento\Framework\DB\Select
     */
    private function buildAffectedMultiselectEntitiesSelect(
        AbstractModel $attribute,
        string $backendTable,
        string $linkField,
        array $changedOptionIds,
        array $storeIds
    ): \Magento\Framework\DB\Select {
        $connection = $this->resourceConnection->getConnection();
        $joinCondition = sprintf('eav.%1$s = p.%1$s', $linkField)
            . $connection->quoteInto(' AND eav.attribute_id = ?', (int) $attribute->getAttributeId())
            . $connection->quoteInto(' AND eav.store_id IN (?)', $storeIds);

        $orParts = [];
        foreach ($changedOptionIds as $optionId) {
            $orParts[] = $connection->quoteInto('FIND_IN_SET(?, eav.value) > 0', (int) $optionId);
        }

        return $connection->select()
            ->distinct()
            ->from(
                ['p' => $this->resourceConnection->getTableName($this->mainTable)],
                ['entity_id' => 'p.entity_id']
            )
            ->joinInner(
                ['eav' => $this->resourceConnection->getTableName($backendTable)],
                $joinCondition,
                []
            )
            ->where(implode(' OR ', $orParts));
    }

    /**
     * Log a scheduling error without interrupting the attribute save.
     *
     * @param AbstractModel $object
     * @param \Throwable|\Exception $e
     *
     * @return void
     */
    private function logError(AbstractModel $object, \Throwable|\Exception $e): void
    {
        $this->logger->error(
            sprintf(
                'CDE03-21 Feed sync scheduling error on attribute {%s} option change. Run resync. Error: %s',
                $object->getAttributeId(),
                $e->getMessage()
            ),
            ['exception' => $e]
        );
    }
}
