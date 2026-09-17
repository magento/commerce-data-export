<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Integration\Unit\Model\Eav;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\CatalogDataExporter\Model\Eav\AttributeOptionLabelChangeResync;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Mview\ViewInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AttributeOptionLabelChangeResyncTest extends TestCase
{
    private ResourceConnection&MockObject $resourceConnection;
    private IndexerRegistry&MockObject $indexerRegistry;
    private CommerceDataExportLoggerInterface&MockObject $logger;
    private bool $indexerGetCalled;
    private bool $loggerErrorCalled;

    protected function setUp(): void
    {
        $this->indexerGetCalled = false;
        $this->loggerErrorCalled = false;

        $select = $this->createMock(Select::class);
        $select->method('from')->willReturn($select);
        $select->method('joinInner')->willReturn($select);
        $select->method('where')->willReturn($select);

        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchAll')->willReturn([]);

        $this->resourceConnection = $this->createMock(ResourceConnection::class);
        $this->resourceConnection->method('getConnection')->willReturn($connection);
        $this->resourceConnection->method('getTableName')->willReturnArgument(0);

        $view = $this->createMock(ViewInterface::class);
        $view->method('isEnabled')->willReturn(false);
        $indexer = $this->createMock(IndexerInterface::class);
        $indexer->method('getView')->willReturn($view);

        $this->indexerRegistry = $this->createMock(IndexerRegistry::class);
        $this->indexerRegistry->method('get')->willReturnCallback(function () use ($indexer) {
            $this->indexerGetCalled = true;
            return $indexer;
        });

        $this->logger = $this->createMock(CommerceDataExportLoggerInterface::class);
        $this->logger->method('error')->willReturnCallback(function () {
            $this->loggerErrorCalled = true;
        });
    }

    /**
     * Given labels changed for option id 5, verify whether scheduling proceeded past
     * the backend-table validation by checking whether the indexer was looked up.
     */
    private function runAfterSaveWithChangedOption(Attribute&MockObject $attribute): void
    {
        $resync = new AttributeOptionLabelChangeResync(
            $this->resourceConnection,
            $this->createMock(EavConfig::class),
            $this->indexerRegistry,
            $this->createMock(MetadataPool::class),
            $this->logger,
            'catalog_product',
            ProductInterface::class,
            'catalog_product_entity',
            'catalog_data_exporter_products'
        );

        $oldLabels = [5 => [1 => 'Old Label']];
        $resync->afterSave($attribute, $oldLabels);
    }

    private function createAttribute(string $backendType, string $backendTable): Attribute&MockObject
    {
        $attribute = $this->getMockBuilder(Attribute::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBackendType', 'getBackendTable', 'getFrontendInput', 'getAttributeId'])
            ->getMock();
        $attribute->method('getBackendType')->willReturn($backendType);
        $attribute->method('getBackendTable')->willReturn($backendTable);
        $attribute->method('getFrontendInput')->willReturn('select');
        $attribute->method('getAttributeId')->willReturn(42);

        return $attribute;
    }

    public function testSkipsSchedulingWhenBackendTypeIsNotAValidEavBackendType(): void
    {
        // A static attribute (or any non-EAV backend type): getBackendTable() falls back to
        // the entity table itself - non-empty, but not a valid (attribute_id, store_id, value)
        // table to join against.
        $attribute = $this->createAttribute('', 'catalog_product_entity');

        $this->runAfterSaveWithChangedOption($attribute);

        $this->assertFalse(
            $this->indexerGetCalled,
            'Scheduling must not proceed for an attribute with no valid EAV backend table'
        );
        $this->assertFalse($this->loggerErrorCalled, 'Skip must be silent, not exception-driven');
    }

    public function testSchedulesResyncForSupportedBackendType(): void
    {
        $attribute = $this->createAttribute('int', 'catalog_product_entity_int');

        $this->runAfterSaveWithChangedOption($attribute);

        $this->assertTrue(
            $this->indexerGetCalled,
            'Scheduling must proceed as before for a supported EAV backend type'
        );
        $this->assertFalse($this->loggerErrorCalled);
    }
}
