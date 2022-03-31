<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Uuid;

use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\DataObject\IdentityService;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\DataExporter\Uuid\ResourceModel\UuidResource;

/**
 * Allows get UUID for given entity and assign/bulk assign UUID.
 * Guarantee assigned UUID is unique across all entities
 */
class UuidManager
{
    private const MAX_UUID_SAVE_ATTEMPTS = 10;

    /**
     * @var UuidResource
     */
    private $salesOrderResource;

    /**
     * @var IdentityService
     */
    private $identityService;

    /**
     * @var CommerceDataExportLoggerInterface
     */
    private $logger;

    /**
     * @param IdentityService $identityService
     * @param CommerceDataExportLoggerInterface $logger
     * @param UuidResource $salesOrderResource
     */
    public function __construct(
        IdentityService $identityService,
        CommerceDataExportLoggerInterface $logger,
        UuidResource $salesOrderResource
    ) {
        $this->salesOrderResource = $salesOrderResource;
        $this->identityService = $identityService;
        $this->logger = $logger;
    }

    /**
     * Assign UUID to given entity. Return assigned UUID
     * Thrown Exception on failure
     *
     * @param int $entityId
     * @param string $type
     * @return string assigned UUID if any
     * @throw UuidSaveException
     */
    public function assign(int $entityId, string $type): string
    {
        $uuid = $this->assignBulk([$entityId], $type);
        if (!isset($uuid[$entityId])) {
            throw new \LogicException(\sprintf('UUID not found for type: %s, ids: %s', $type, $entityId));
        }
        return $uuid[$entityId];
    }

    /**
     * Assign UUIDs to given entities. Return assigned UUIDs
     * Thrown Exception on failure
     *
     * @param int[] $entityIds
     * @param string $type
     * @return string[] Map of UUID assigned to entity if any in format [entityId => UUID, ...]
     * @throw UuidSaveException
     */
    public function assignBulk(array $entityIds, string $type): array
    {
        [$entityIds, $assigned] = $this->filterAssignedIds($entityIds, $type);
        if (!$entityIds) {
            return $assigned;
        }
        $duplicates = [];
        $attempts = 0;
        while ($attempts < self::MAX_UUID_SAVE_ATTEMPTS) {
            $data = $this->generateBulkData($entityIds, $type);
            try {
                $this->salesOrderResource->saveBulk($data);
            } catch (AlreadyExistsException $e) {
                $attempts++;
                $duplicates[] = \array_column($data, 'uuid');
                continue ;
            } catch (\Throwable $e) {
                $attempts++;
                $this->logger->error(
                    \sprintf('Failed to assign UUID for type: %s, ids: %s', $type, implode(',', $entityIds)),
                    ['exception' => $e]
                );
                continue ;
            }
            $assigned += \array_column($data, 'uuid', 'entity_id');
            return $assigned;
        }

        $error = \sprintf(
            'Failed to assign UUID for type: %s, ids: %s. duplicates: %s',
            $type,
            implode(',', $entityIds),
            $duplicates ? implode(',', \array_merge(...$duplicates)) : ''
        );
        $this->logger->error($error);

        throw new UuidSaveException($error);
    }

    /**
     * Get UUID for given entity
     *
     * @param int $entityId
     * @param string $type
     * @return bool
     */
    public function isAssigned(int $entityId, string $type): bool
    {
        return (boolean) $this->salesOrderResource->getAssignedIds([$entityId], $type);
    }

    /**
     * Return only ids without assigned UUID
     *
     * @param array $entityIds
     * @param string $type
     * @return array
     */
    private function filterAssignedIds(array $entityIds, string $type): array
    {
        $assigned = $this->salesOrderResource->getAssignedIds($entityIds, $type);
        return [
            $assigned ? array_diff($entityIds, \array_keys($assigned)) : $entityIds,
            $assigned ?? []
        ];
    }

    /**
     * @param array $entityIds
     * @param string $type
     * @return array
     */
    private function generateBulkData(array $entityIds, string $type): array
    {
        $data = [];
        foreach ($entityIds as $entityId) {
            $data[$entityId] = [
                'entity_id' => $entityId,
                'uuid' => $this->identityService->generateId(),
                'type' => $type
            ];
        }
        return $data;
    }
}
