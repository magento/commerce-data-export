<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Integration\Unit\Model\Provider\EavAttributes;

use Magento\CatalogDataExporter\Model\Provider\EavAttributes\EavAttributeOptionValueResolver;
use Magento\Framework\App\ErrorHandler;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for EavAttributeOptionValueResolver.
 */
class EavAttributeOptionValueResolverTest extends TestCase
{
    /**
     * Option rows with a null store code must not break label resolution under Magento's error handler.
     *
     * The options query LEFT JOINs `store`, so the store code is null for a label whose store_id has no
     * store, and for an option with no label rows at all. PHP 8.5 deprecates null array offsets.
     */
    public function testResolvesLabelsWhenSomeOptionRowsHaveNoStoreCode(): void
    {
        $resolver = $this->createResolver([
            $this->optionRow('10', 'Small', 'admin'),
            $this->optionRow('10', 'Petit', 'fr_ca'),
            $this->optionRow('10', 'Label for a deleted store', null),
            $this->optionRow('11', null, null),
        ]);

        // Same setup as app/bootstrap.php, where deprecations become exceptions.
        $errorReporting = error_reporting(E_ALL);
        set_error_handler([new ErrorHandler(), 'handler']);
        try {
            $this->assertTrue($resolver->hasOptions('size'));
            $this->assertSame(['Petit'], $resolver->getAttributeValue('size', 'fr_ca', '10'));
            $this->assertSame(['Small'], $resolver->getAttributeValue('size', 'en_ca', '10'));
            $this->assertSame(['Petit', '11'], $resolver->getAttributeValue('size', 'fr_ca', '10,11'));
        } finally {
            restore_error_handler();
            error_reporting($errorReporting);
        }
    }

    /**
     * @param array $optionRows
     * @return EavAttributeOptionValueResolver
     */
    private function createResolver(array $optionRows): EavAttributeOptionValueResolver
    {
        $select = $this->createMock(Select::class);
        foreach (['from', 'join', 'joinLeft', 'where'] as $method) {
            $select->method($method)->willReturnSelf();
        }

        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('query')->willReturnOnConsecutiveCalls(
            $this->cursor([['attribute_code' => 'size', 'frontend_input' => 'select', 'source_model' => null]]),
            $this->cursor($optionRows)
        );

        $resourceConnection = $this->createMock(ResourceConnection::class);
        $resourceConnection->method('getConnection')->willReturn($connection);
        $resourceConnection->method('getTableName')->willReturnArgument(0);

        return new EavAttributeOptionValueResolver($resourceConnection);
    }

    /**
     * @param array $rows
     * @return \Zend_Db_Statement_Interface
     */
    private function cursor(array $rows): \Zend_Db_Statement_Interface
    {
        $cursor = $this->createMock(\Zend_Db_Statement_Interface::class);
        $cursor->method('fetch')->willReturnOnConsecutiveCalls(...[...$rows, false]);

        return $cursor;
    }

    /**
     * @param string $optionId
     * @param string|null $optionValue
     * @param string|null $storeViewCode
     * @return array
     */
    private function optionRow(string $optionId, ?string $optionValue, ?string $storeViewCode): array
    {
        return [
            'attribute_code' => 'size',
            'source_model' => null,
            'optionId' => $optionId,
            'optionValue' => $optionValue,
            'storeViewCode' => $storeViewCode,
        ];
    }
}
