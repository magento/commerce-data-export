<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\ProductReviewSummaryAttributes\Test\Integration\Unit\Model;

use Magento\CatalogDataExporter\Service\SystemAttributeRegistrar;
use Magento\CatalogDataExporter\Service\SystemAttributeRegistrarFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use AdobeCommerce\ProductReviewSummaryAttributes\Model\ReviewSummaryAttributeRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReviewSummaryAttributeRegistryTest extends TestCase
{
    private const CONFIGURATION_MAP = [
        'ac_review_count' => [
            'properties' => ['label' => 'Review Count', 'type' => 'decimal', 'input' => 'numeric'],
            'exporterOverrides' => ['visible' => false],
        ],
        'ac_rating_summary' => [
            'properties' => ['label' => 'Average Rating %'],
        ],
    ];

    /** @var SystemAttributeRegistrarFactory&MockObject */
    private SystemAttributeRegistrarFactory $registrarFactory;

    /** @var AdapterInterface&MockObject */
    private AdapterInterface $connection;

    /** @var ReviewSummaryAttributeRegistry */
    private ReviewSummaryAttributeRegistry $registry;

    protected function setUp(): void
    {
        $this->registrarFactory = $this->createMock(SystemAttributeRegistrarFactory::class);
        $this->connection = $this->createMock(AdapterInterface::class);
        $select = $this->createMock(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $this->connection->method('select')->willReturn($select);

        $resourceConnection = $this->createMock(ResourceConnection::class);
        $resourceConnection->method('getConnection')->willReturn($this->connection);
        $resourceConnection->method('getTableName')->willReturnArgument(0);

        $entityType = $this->createMock(Type::class);
        $entityType->method('getId')->willReturn(4);
        $eavConfig = $this->createMock(EavConfig::class);
        $eavConfig->method('getEntityType')->willReturn($entityType);

        $this->registry = new ReviewSummaryAttributeRegistry(
            $this->registrarFactory,
            $resourceConnection,
            $eavConfig,
            self::CONFIGURATION_MAP
        );
    }

    public function testConfiguration(): void
    {
        $this->assertSame(['ac_review_count', 'ac_rating_summary'], $this->registry->getAttributeCodes());
        $this->assertSame(['visible' => false], $this->registry->getExporterOverride('ac_review_count'));
        $this->assertSame([], $this->registry->getExporterOverride('ac_rating_summary'));
        $this->assertSame([], $this->registry->getExporterOverride('color'));
    }

    public function testIsRegisteredRequiresAllAttributes(): void
    {
        $this->connection->expects($this->exactly(2))
            ->method('fetchAll')
            ->willReturnOnConsecutiveCalls(
                [$this->attribute('ac_review_count')],
                [$this->attribute('ac_review_count'), $this->attribute('ac_rating_summary', 'int', 'text')]
            );

        $this->assertFalse($this->registry->isRegistered());
        $this->assertTrue($this->registry->isRegistered());
        // Positive result is cached
        $this->assertTrue($this->registry->isRegistered());
    }

    /**
     * @param array $attribute
     * @return void
     * @dataProvider merchantAttributeProvider
     */
    #[DataProvider('merchantAttributeProvider')]
    public function testMerchantAttributeWithSameCodeIsNotRegistered(array $attribute): void
    {
        $this->connection->method('fetchAll')
            ->willReturn([$attribute, $this->attribute('ac_rating_summary')]);

        $this->assertFalse($this->registry->isRegistered());
    }

    /**
     * @return array
     */
    public static function merchantAttributeProvider(): array
    {
        return [
            'user defined' => [
                ['attribute_code' => 'ac_review_count', 'is_user_defined' => '1',
                    'backend_type' => 'decimal', 'frontend_input' => 'numeric'],
            ],
            'other backend type' => [
                ['attribute_code' => 'ac_review_count', 'is_user_defined' => '0',
                    'backend_type' => 'varchar', 'frontend_input' => 'numeric'],
            ],
            'other input' => [
                ['attribute_code' => 'ac_review_count', 'is_user_defined' => '0',
                    'backend_type' => 'decimal', 'frontend_input' => 'text'],
            ],
        ];
    }

    public function testRegisterUsesOwnConfigurationOnly(): void
    {
        $registrar = $this->createMock(SystemAttributeRegistrar::class);
        $registrar->expects($this->once())->method('execute');
        $this->registrarFactory->expects($this->once())
            ->method('create')
            ->with(['configurationMap' => self::CONFIGURATION_MAP])
            ->willReturn($registrar);

        $this->registry->register();
    }

    /**
     * Stored system attribute row; type and input are not configured for ac_rating_summary
     *
     * @param string $code
     * @param string $backendType
     * @param string $frontendInput
     * @return array
     */
    private function attribute(string $code, string $backendType = 'decimal', string $frontendInput = 'numeric'): array
    {
        return [
            'attribute_code' => $code,
            'is_user_defined' => '0',
            'backend_type' => $backendType,
            'frontend_input' => $frontendInput,
        ];
    }
}
