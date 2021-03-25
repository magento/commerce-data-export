<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Api;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Tests configurable product export
 * @magentoAppIsolation enabled
 */
class ConfigurableProductExportTest extends AbstractProductExportTestHelper
{
    /**
     * Attributes to compare for configurable product
     *
     * @var string[]
     */
    protected $attributesToCompare = [
        'sku',
        'name',
        'type',
        'status',
        'taxClassId',
        'createdAt',
        'updatedAt',
        'urlKey',
        'visibility',
        'currency',
        'displayable',
        'buyable',
        'options',
        'variants',
        'categories',
        'inStock',
        'lowStock',
        'url',
    ];

    /**
     * Test product export REST API
     *
     * @magentoApiDataFixture Magento/CatalogRule/_files/configurable_product.php
     *
     * @return void
     */
    public function testExport(): void
    {
        self::markTestSkipped('Should be migrated to integration test');
        $this->_markTestAsRestOnly('SOAP will be covered in another test');
        $this->runIndexer();

        try {
            $product = $this->productRepository->get('configurable');
        } catch (NoSuchEntityException $e) {
            $this->fail("Couldn`t find product with sku 'simple' " . $e->getMessage());
        }

        if (isset($product)) {
            /** @see \Magento\CatalogDataExporterApi\Api\EntityRequest and \Magento\CatalogDataExporterApi\Api\EntityRequest\Item */
            $request = [
                'request' => [
                    'entities' => [
                        'entity1' => [
                            'entityId' => (int)$product->getId()
                        ],
                    ],
                ],
            ];
            $this->createServiceInfo['rest']['resourcePath'] .= '?' . \http_build_query($request);
            $result = $this->_webApiCall($this->createServiceInfo);

            $expected = $this->productsFeed->getFeedByIds([$product->getId()])['feed'];

            $this->assertProductsEquals($expected, $result);
        }
    }
}
