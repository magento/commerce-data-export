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

namespace Magento\DataExporter\Test\Integration\Uuid;

use Magento\DataExporter\Uuid\UuidManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class UuidManagerTest extends TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface|mixed
     */
    private $objectManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @param array $entityIds
     * @param string $type
     * @return void
     * @dataProvider happyPathDataProvider
     */
    public function testHappyPath(array $entityIds, string $type): void
    {
        /** @var UuidManager $uuidManager */
        $uuidManager = $this->objectManager->create(UuidManager::class);

        if (count($entityIds) === 1) {
            $uuidManager->assign(current($entityIds), $type);
        } else {
            $uuidManager->assignBulk($entityIds, $type);
        }
        foreach ($entityIds as $entityId) {
            self::assertTrue(
                $uuidManager->isAssigned($entityId, $type),
                'uuid is not assigned for ' . $entityId . ' ' . $type
            );
        }
    }

    /**
     * @return void
     */
    public function testUuidAssignedOnlyOnce(): void
    {
        /** @var UuidManager $uuidManager */
        $uuidManager = $this->objectManager->create(UuidManager::class);

        $assignedUuid = $uuidManager->assign(1, 'just-once');
        self::assertNotEmpty($assignedUuid);
        $assignedUuidRepeat = $uuidManager->assign(1, 'just-once');
        self::assertEquals($assignedUuid, $assignedUuidRepeat);

        $assignedUuids = $uuidManager->assignBulk([3, 4], 'just-once');
        $assignedUuidsRepeat = $uuidManager->assignBulk([4, 3, 4, 3], 'just-once');

        self::assertCount(2, $assignedUuids);
        self::assertEquals($assignedUuids, $assignedUuidsRepeat);
    }

    /**
     * @return void
     */
    public function testUuidDuplicatesGenerated(): void
    {
        $uuidGeneratorMock = $this->createMock(\Magento\Framework\DataObject\IdentityService::class);
        /** @var UuidManager $uuidManager */
        $uuidManager = $this->objectManager->create(UuidManager::class, ['identityService' => $uuidGeneratorMock]);
        $uuidGeneratorMock->method('generateId')->willReturn('uuid');

        $this->expectException(\Magento\DataExporter\Uuid\UuidSaveException::class);
        $this->expectExceptionMessage('Failed to assign UUID for type: test-type, ids: 8,9. duplicates: uuid');

        $uuidManager->assignBulk([8, 9], 'test-type');

    }
    /**
     * @return array[]
     */
    public function happyPathDataProvider(): array
    {
        return [
            [
                [1], //entityIds
                'test-type', // type
            ],
            [
                [1, 2], //entityIds
                'test-type', // type
            ],
            [
                [3, 4], //entityIds
                'test-type', // type
            ],
            [
                [4, 4, 5, 4, 5, 5], //entityIds
                'test-type', // type
            ]
        ];
    }
}
