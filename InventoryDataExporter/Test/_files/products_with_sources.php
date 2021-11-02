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

use Magento\Framework\Api\DataObjectHelper;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryApi\Api\Data\StockInterfaceFactory;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceInterfaceFactory;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterfaceFactory;
use Magento\InventoryApi\Api\StockSourceLinksSaveInterface;

use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;

use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;

/**
 * Generate the following structure:
 *
 * :::Stocks:::
 * EU_stock (id: 10)
 *  - eu-1
 *  - eu-2
 *
 * US_stock (id: 20)
 *  - us-1
 *  - us-2 (OUT OF STOCK)
 *
 * Global_stock (id: 30)
 *  - eu-1
 *  - us-1
 *
 * :::Products:::
 *
 * product_with_default_stock_only
 *  Default Stock qty: 8.5
 *
 * product_with_disabled_manage_stock
 *  manage_stock = false
 *
 * product_with_enabled_backorders
 *  min_stock = 0 (with enabled backorders and out-of-Stock Threshold = 0 we assume infinitive stock 
 *
 * product_in_EU_stock_with_2_sources
 * - eu-1 - 5.5qty (stock: 10, 30)
 * - eu-2 - 4qty   (stock: 10)
 *
 * product_in_Global_stock_with_3_sources
 * - eu-1 - 1qty (stock: 10, 30)
 * - eu-2 - 2qty (stock: 10)
 * - us-1 - 4qty (stock: 20, 30)
 *
 * product_in_US_stock_with_disabled_source
 * - us-2 - 5qty

 */

/**
 * Create stocks
 */
$createStocks = static function (): void
{
    /** @var StockInterfaceFactory $stockFactory */
    $stockFactory = Bootstrap::getObjectManager()->get(StockInterfaceFactory::class);
    /** @var DataObjectHelper $dataObjectHelper */
    $dataObjectHelper = Bootstrap::getObjectManager()->get(DataObjectHelper::class);
    /** @var StockRepositoryInterface $stockRepository */
    $stockRepository = Bootstrap::getObjectManager()->get(StockRepositoryInterface::class);

    $stocksData = [
        [
            // define only required and needed for tests fields
            StockInterface::STOCK_ID => 10,
            StockInterface::NAME => 'EU_stock',
        ],
        [
            StockInterface::STOCK_ID => 20,
            StockInterface::NAME => 'US_stock',
        ],
        [
            StockInterface::STOCK_ID => 30,
            StockInterface::NAME => 'Global_stock',
        ]
    ];
    foreach ($stocksData as $stockData) {
        /** @var StockInterface $stock */
        $stock = $stockFactory->create();
        $dataObjectHelper->populateWithArray($stock, $stockData, StockInterface::class);
        $stockRepository->save($stock);
    }
};

/**
 * Create Sources
 */
$createSources = static function (): void
{
    /** @var SourceInterfaceFactory $sourceFactory */
    $sourceFactory = Bootstrap::getObjectManager()->get(SourceInterfaceFactory::class);
    /** @var DataObjectHelper $dataObjectHelper */
    $dataObjectHelper = Bootstrap::getObjectManager()->get(DataObjectHelper::class);
    /** @var SourceRepositoryInterface $sourceRepository */
    $sourceRepository = Bootstrap::getObjectManager()->get(SourceRepositoryInterface::class);

    $sourcesData = [
        [
            // define only required and needed for tests fields
            SourceInterface::SOURCE_CODE => 'eu-1',
            SourceInterface::NAME => 'EU-source-1',
            SourceInterface::ENABLED => true,
            SourceInterface::POSTCODE => 'postcode',
            SourceInterface::COUNTRY_ID => 'FR',
        ],
        [
            SourceInterface::SOURCE_CODE => 'eu-2',
            SourceInterface::NAME => 'EU-source-2',
            SourceInterface::ENABLED => true,
            SourceInterface::POSTCODE => 'postcode',
            SourceInterface::COUNTRY_ID => 'FR',
        ],
        [
            SourceInterface::SOURCE_CODE => 'us-1',
            SourceInterface::NAME => 'US-source-1',
            SourceInterface::ENABLED => true,
            SourceInterface::POSTCODE => 'postcode',
            SourceInterface::COUNTRY_ID => 'US',
        ],
        [
            SourceInterface::SOURCE_CODE => 'us-2',
            SourceInterface::NAME => 'US-source-2',
            SourceInterface::ENABLED => true,
            SourceInterface::POSTCODE => 'postcode',
            SourceInterface::COUNTRY_ID => 'US',
        ],
    ];
    foreach ($sourcesData as $sourceData) {
        /** @var SourceInterface $source */
        $source = $sourceFactory->create();
        $dataObjectHelper->populateWithArray($source, $sourceData, SourceInterface::class);
        $sourceRepository->save($source);
    }
};

/**
 * Link Source to Stocks
 */
$assignSourceToStock = static function (): void
{
    /** @var DataObjectHelper $dataObjectHelper */
    $dataObjectHelper = Bootstrap::getObjectManager()->get(DataObjectHelper::class);
    /** @var StockSourceLinksSaveInterface $stockSourceLinksSave */
    $stockSourceLinksSave = Bootstrap::getObjectManager()->get(StockSourceLinksSaveInterface::class);
    /** @var StockSourceLinkInterfaceFactory $stockSourceLinkFactory */
    $stockSourceLinkFactory = Bootstrap::getObjectManager()->get(StockSourceLinkInterfaceFactory::class);

    $linksData = [
        [
            StockSourceLinkInterface::STOCK_ID => 10,
            StockSourceLinkInterface::SOURCE_CODE => 'eu-1',
            StockSourceLinkInterface::PRIORITY => 1,
        ],
        [
            StockSourceLinkInterface::STOCK_ID => 10,
            StockSourceLinkInterface::SOURCE_CODE => 'eu-2',
            StockSourceLinkInterface::PRIORITY => 2,
        ],
        [
            StockSourceLinkInterface::STOCK_ID => 20,
            StockSourceLinkInterface::SOURCE_CODE => 'us-1',
            StockSourceLinkInterface::PRIORITY => 1,
        ],
        [
            StockSourceLinkInterface::STOCK_ID => 20,
            StockSourceLinkInterface::SOURCE_CODE => 'us-2',
            StockSourceLinkInterface::PRIORITY => 1,
        ],
        [
            StockSourceLinkInterface::STOCK_ID => 30,
            StockSourceLinkInterface::SOURCE_CODE => 'eu-1',
            StockSourceLinkInterface::PRIORITY => 1,
        ],
        [
            StockSourceLinkInterface::STOCK_ID => 30,
            StockSourceLinkInterface::SOURCE_CODE => 'us-1',
            StockSourceLinkInterface::PRIORITY => 2,
        ],
    ];

    $links = [];
    foreach ($linksData as $linkData) {
        /** @var StockSourceLinkInterface $link */
        $link = $stockSourceLinkFactory->create();
        $dataObjectHelper->populateWithArray($link, $linkData, StockSourceLinkInterface::class);
        $links[] = $link;
    }
    $stockSourceLinksSave->execute($links);
};

$createProducts = static function ()
{
    $objectManager = Bootstrap::getObjectManager();
    /** @var ProductInterfaceFactory $productFactory */
    $productFactory = $objectManager->get(ProductInterfaceFactory::class);
    /** @var ProductRepositoryInterface $productRepository */
    $productRepository = $objectManager->get(ProductRepositoryInterface::class);
    $productRepository->cleanCache();

    $stockData = [
        'product_with_default_stock_only' => [
            'qty' => 8.5,
            'is_in_stock' => true,
            'manage_stock' => true,
            'is_qty_decimal' => true
        ],
        'product_in_default_and_2_EU_sources' => [
            'qty' => 11.5,
            'is_in_stock' => true,
            'manage_stock' => true,
            'is_qty_decimal' => true
        ],
        'product_with_disabled_manage_stock' => [
            'use_config_manage_stock' => false,
            'manage_stock' => false,
        ],
        'product_with_enabled_backorders' => [
            'qty' => 5,
            'is_in_stock' => true,
            'manage_stock' => true,
            'min_qty' => 0,
            'backorders' => true
        ],
        'product_in_EU_stock_with_2_sources' => [
            'qty' => 0,
            'is_in_stock' => true,
            'is_qty_decimal' => true,
            'manage_stock' => true
        ],
        'product_in_Global_stock_with_3_sources' => [
            'qty' => 0,
            'is_in_stock' => true,
            'manage_stock' => true
        ],
        'product_in_US_stock_with_disabled_source' => [
            'qty' => 5,
            'is_in_stock' => true,
            'manage_stock' => true
        ],
    ];

    foreach ($stockData as $sku => $productStockData) {
        $product = $productFactory->create();
        $product->setTypeId(Type::TYPE_SIMPLE)
            ->setAttributeSetId(4)
            ->setName('Simple Product ' . $sku)
            ->setSku($sku)
            ->setPrice(10)
            ->setStockData($productStockData)
            ->setStatus(Status::STATUS_ENABLED);
        $productRepository->save($product);
    }
};

$assignProductsToSources = static function (): void
{
    /** @var DataObjectHelper $dataObjectHelper */
    $dataObjectHelper = Bootstrap::getObjectManager()->get(DataObjectHelper::class);
    /** @var SourceItemInterfaceFactory $sourceItemFactory */
    $sourceItemFactory = Bootstrap::getObjectManager()->get(SourceItemInterfaceFactory::class);
    /** @var  SourceItemsSaveInterface $sourceItemsSave */
    $sourceItemsSave = Bootstrap::getObjectManager()->get(SourceItemsSaveInterface::class);

    $sourcesItemsData = [
        [
            SourceItemInterface::SOURCE_CODE => 'default',
            SourceItemInterface::SKU => 'product_in_default_and_2_EU_sources',
            SourceItemInterface::QUANTITY => 2,
            SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
        ],
        [
            SourceItemInterface::SOURCE_CODE => 'eu-1',
            SourceItemInterface::SKU => 'product_in_default_and_2_EU_sources',
            SourceItemInterface::QUANTITY => 5.5,
            SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
        ],
        [
            SourceItemInterface::SOURCE_CODE => 'eu-2',
            SourceItemInterface::SKU => 'product_in_default_and_2_EU_sources',
            SourceItemInterface::QUANTITY => 4,
            SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
        ],
        [
            SourceItemInterface::SOURCE_CODE => 'eu-1',
            SourceItemInterface::SKU => 'product_in_EU_stock_with_2_sources',
            SourceItemInterface::QUANTITY => 5.5,
            SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
        ],
        [
            SourceItemInterface::SOURCE_CODE => 'eu-2',
            SourceItemInterface::SKU => 'product_in_EU_stock_with_2_sources',
            SourceItemInterface::QUANTITY => 4,
            SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
        ],
        [
            SourceItemInterface::SOURCE_CODE => 'eu-1',
            SourceItemInterface::SKU => 'product_in_Global_stock_with_3_sources',
            SourceItemInterface::QUANTITY => 1,
            SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
        ],
        [
            SourceItemInterface::SOURCE_CODE => 'eu-2',
            SourceItemInterface::SKU => 'product_in_Global_stock_with_3_sources',
            SourceItemInterface::QUANTITY => 2,
            SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
        ],
        [
            SourceItemInterface::SOURCE_CODE => 'us-1',
            SourceItemInterface::SKU => 'product_in_Global_stock_with_3_sources',
            SourceItemInterface::QUANTITY => 4,
            SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
        ],
        [
            SourceItemInterface::SOURCE_CODE => 'us-2',
            SourceItemInterface::SKU => 'product_in_US_stock_with_disabled_source',
            SourceItemInterface::QUANTITY => 5,
            SourceItemInterface::STATUS => SourceItemInterface::STATUS_OUT_OF_STOCK,
        ],
    ];

    $sourceItems = [];
    foreach ($sourcesItemsData as $sourceItemData) {
        /** @var SourceItemInterface $source */
        $sourceItem = $sourceItemFactory->create();
        $dataObjectHelper->populateWithArray($sourceItem, $sourceItemData, SourceItemInterface::class);
        $sourceItems[] = $sourceItem;
    }
    $sourceItemsSave->execute($sourceItems);
};

$createStocks();
$createSources();
$assignSourceToStock();
$createProducts();
$assignProductsToSources();
