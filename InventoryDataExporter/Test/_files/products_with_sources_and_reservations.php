<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 *
 * Create Inventory entities:
 * - Stocks
 * - Sources assigned to Stocks
 * - products assigned to Stocks & default stocks
 */
declare(strict_types=1);

use Magento\InventoryReservationsApi\Model\AppendReservationsInterface;
use Magento\InventoryReservationsApi\Model\ReservationBuilderInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;

use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

use Magento\Catalog\Api\ProductRepositoryInterface;

Resolver::getInstance()->requireDataFixture(
    'Magento_InventoryDataExporter::Test/_files/products_with_sources.php'
);

/**
 * Create reservations
 */
$createReservations = static function (): void
{
    $productsList = [
        [
            'sku' => 'product_with_default_stock_only',
            'qty_by_stocks' => [
                ['stock_id' => 1, 'qty' => -2.2] //default stock 5.5 left
            ],
        ],
        [
            'sku' => 'product_with_disabled_manage_stock',
            'qty_by_stocks' => [
                ['stock_id' => 1, 'qty' => -2.2]  //unlimited
            ]
        ],
        [
            'sku' => 'product_with_enabled_backorders',
            'qty_by_stocks' => [
                ['stock_id' => 1, 'qty' => -7.2]  //unlimited
            ]
        ],
        [
            'sku' => 'product_in_EU_stock_with_2_sources',
            'qty_by_stocks' => [
                ['stock_id' => 10, 'qty' => -9.5]  //eu-1, eu-2 - 4.5 left
            ]
        ],
        [
            'sku' => 'product_in_default_and_2_EU_sources',
            'qty_by_stocks' => [
                ['stock_id' => 10, 'qty' => -4],  //eu-1, eu-2 - 5.5 left
                ['stock_id' => 1, 'qty' => -1],  //default - 1 left
            ]
        ],
        [
            'sku' => 'product_in_Global_stock_with_3_sources',
            'qty_by_stocks' => [
                ['stock_id' => 10, 'qty' => -3],  //eu-1, eu-2 - 4.5 left
                ['stock_id' => 20, 'qty' => -2],  //us-1 - 2 left
                ['stock_id' => 30, 'qty' => -2.5],
            ]
        ]
    ];

    $objectManager = Bootstrap::getObjectManager();
    /** @var ProductRepositoryInterface $productRepository */
    $productRepository = $objectManager->create(ProductRepositoryInterface::class);
    /** @var ReservationBuilderInterface $reservationBuilder */
    $reservationBuilder = $objectManager->get(ReservationBuilderInterface::class);
    $isSourceItemManagementAllowedForProductType = $objectManager->get(
        IsSourceItemManagementAllowedForProductTypeInterface::class
    );
    /** @var AppendReservationsInterface $appendReservations */
    $appendReservations = $objectManager->get(AppendReservationsInterface::class);
    $reservations = [];
    foreach ($productsList as $productData) {
        $product = $productRepository->get($productData['sku']);
        $skusToReindex[] = $productData['sku'];
        if ($isSourceItemManagementAllowedForProductType->execute($product->getTypeId())) {
            foreach ($productData['qty_by_stocks'] as $stockData) {
                $reservations[] = $reservationBuilder
                    ->setSku($productData['sku'])
                    ->setQuantity((float)$stockData['qty'])
                    ->setStockId($stockData['stock_id'])
                    ->setMetadata()
                    ->build();
            }
        }
    }

    $appendReservations->execute($reservations);
};

$createReservations();
