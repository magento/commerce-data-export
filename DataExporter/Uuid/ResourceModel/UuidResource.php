<?php
/**
 * Copyright 2022 Adobe
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

namespace Magento\DataExporter\Uuid\ResourceModel;

use Magento\Framework\DB\Adapter\DuplicateException;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\AbstractDb;

/**
 * UUID resource model
 */
class UuidResource extends AbstractDb
{
    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_setMainTable('data_exporter_uuid');
    }

    /**
     * Get entity ids with assigned UUIDs
     *
     * @param int[] $entityId
     * @param string $type
     * @return array|null
     */
    public function getAssignedIds(array $entityId, string $type): ?array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), ['entity_id', 'uuid'])
            ->where('entity_id IN (?)', $entityId)
            ->where('type = ?', $type);

        return $connection->fetchPairs($select) ?: null;
    }

    /**
     * @param array $data
     * @return bool
     * @throws AlreadyExistsException
     */
    public function saveBulk(array $data): bool
    {
        $connection = $this->getConnection();
        try {
            $connection->insertMultiple($this->getMainTable(), $data);
        } catch (DuplicateException $e) {
            throw new AlreadyExistsException(__('UUID already exists for given records'));
        }

        return true;
    }
}
