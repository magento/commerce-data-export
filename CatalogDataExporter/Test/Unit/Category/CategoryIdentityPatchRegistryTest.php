<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Unit\Category;

use Magento\CatalogDataExporter\Setup\Patch\Data\RestoreCategoryFeedV1Identity;
use Magento\Framework\Setup\Patch\PatchFactory;
use Magento\Framework\Setup\Patch\PatchHistory;
use Magento\Framework\Setup\Patch\PatchRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Verify upgrade ordering without the removed path-identity patch.
 */
class CategoryIdentityPatchRegistryTest extends TestCase
{
    private const OLD_PATCH = 'Magento\CatalogDataExporter\Setup\Patch\Data\MigrateCategoryFeedIdentity';

    /**
     * The restore patch runs once, regardless of the old patch's history.
     *
     * @param bool $oldPatchApplied
     * @param bool $restorePatchApplied
     * @return void
     */
    #[DataProvider('patchHistoryProvider')]
    public function testRestorePatchRegistration(bool $oldPatchApplied, bool $restorePatchApplied): void
    {
        $history = $this->createMock(PatchHistory::class);
        $history->expects(self::once())->method('isApplied')
            ->with(RestoreCategoryFeedV1Identity::class)->willReturnCallback(
                static fn(string $patch): bool => $patch === self::OLD_PATCH
                    ? $oldPatchApplied
                    : $restorePatchApplied
            );
        $registry = new PatchRegistry($this->createMock(PatchFactory::class), $history);
        $registry->registerPatch(RestoreCategoryFeedV1Identity::class);

        self::assertSame(
            $restorePatchApplied ? [] : [RestoreCategoryFeedV1Identity::class],
            array_values(iterator_to_array($registry))
        );
        self::assertSame([], RestoreCategoryFeedV1Identity::getDependencies());
        $patch = $this->getMockBuilder(RestoreCategoryFeedV1Identity::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['apply'])
            ->getMock();
        self::assertSame([], $patch->getAliases());
        self::assertFalse(class_exists(self::OLD_PATCH), 'The obsolete patch must not be available');
    }

    /**
     * Cover upgrades before and after the old patch, including subsequent upgrades.
     *
     * @return array
     */
    public static function patchHistoryProvider(): array
    {
        return [
            'before path identity' => [false, false],
            'after path identity' => [true, false],
            'restore already applied without old patch' => [false, true],
            'restore already applied with old patch' => [true, true],
        ];
    }
}
