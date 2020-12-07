<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogExport\Test\Api;

use Magento\DataExporter\Model\FeedInterface;
use Magento\DataExporter\Model\FeedPool;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;

/**
 * @magentoAppIsolation enabled
 */
class DynamicAttributesTest extends WebapiAbstract
{
    /**
     * @var array
     */
    private $createServiceInfo;

    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    private $objectManager;

    /**
     * @var FeedInterface
     */
    private $productsFeed;

    /**
     * @var string[]
     */
    private $attributesToCompare = [
        'sku',
        'name',
        'type',
        'status',
        'tax_class_id',
        'created_at',
        'updated_at',
        'url_key',
        'visibility',
        'currency',
        'displayable',
        'buyable',
        'attributes',
        'categories',
        'in_stock',
        'low_stock',
        'url',
    ];

    protected function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->productsFeed = $this->objectManager->get(FeedPool::class)->getFeed('products');

        $this->createServiceInfo = [
            'rest' => [
                'resourcePath' => '/V1/catalog-export/products',
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => 'catalogExportApiProductRepositoryV1',
                'serviceVersion' => 'V1',
                'operation' => 'catalogExportApiProductRepositoryV1Get',
            ],
        ];
    }

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/product_simple_with_custom_attribute.php
     */
    public function testExport()
    {
        $this->_markTestAsRestOnly('SOAP will be covered in another test');

        /** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $product = $productRepository->get('simple');

        /** @see \Magento\CatalogExportApi\Api\EntityRequest and \Magento\CatalogExportApi\Api\EntityRequest\Item */
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

        $this->assertProductsEquals($this->productsFeed->getFeedByIds([$product->getId()])['feed'], $result);
    }

    private function assertProductsEquals(array $expected, array $actual)
    {
        $size = sizeof($expected);
        for ($i = 0; $i < $size; $i++) {
            foreach ($this->attributesToCompare as $attribute) {
                $this->compareComplexValue(
                    $expected[$i][$this->snakeToCamelCase($attribute)],
                    $actual[$i][$attribute]
                );
            }
        }
    }

    private function compareComplexValue($expected, $actual)
    {
        if (is_array($expected)) {
            $actual = !is_array($actual) ? json_decode($actual, true) : $actual;
            foreach (array_keys($expected) as $key) {
                $snakeCaseKey = $this->camelToSnakeCase($key);
                $this->assertTrue(
                    \array_key_exists($snakeCaseKey, $actual),
                    "'$snakeCaseKey' doesn't exist\n"
                    . "expected: \n"
                    . json_encode($expected)
                    . "actual: \n"
                    . json_encode($actual)
                );
                $this->compareComplexValue($expected[$key], $actual[$snakeCaseKey]);
            }
        } else {
            $this->assertEquals($expected, $actual);
        }
    }

    private function snakeToCamelCase($string)
    {
        $string = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
        $string[0] = strtolower($string[0]);
        return $string;
    }

    private function camelToSnakeCase($string)
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $string));
    }

    /**
     * Test boolean attribute
     *
     * @magentoApiDataFixture Magento_CatalogExport::Test/Api/_files/one_product_simple_with_boolean_attribute.php
     */
    public function testBooleanAttribute()
    {
        $result = $this->getProductApiResult('simple_with_boolean');
        if ($this->hasAttributeData($result)) {
            $value = $result[0]['attributes'][0]['value'][0];
            unset($result[0]['attributes'][0]['value']); // re adding as array instead of json
            $actualResult = $result[0]['attributes'][0];
            $actualResult['value'] = $value;
            $expectedResult = [
                'attribute_code' => 'boolean_attribute',
                'type'  => 'boolean',
                'value' => 'yes'
            ];

            $this->assertEquals($expectedResult, $actualResult);
            $this->assertEquals('simple_with_boolean', $result[0]['sku']);
        }
    }

    /**
     * Test Multiselect attribute
     *
     * @magentoApiDataFixture Magento_CatalogExport::Test/Api/_files/one_product_simple_with_multiselect_attribute.php
     */
    public function testMultiselectAttribute()
    {
        $result = $this->getProductApiResult('simple_with_multiselect');
        if ($this->hasAttributeData($result)) {
            $value = $result[0]['attributes'][0]['value'][0];
            unset($result[0]['attributes'][0]['value']); // re adding as array instead of json
            $actualResult = $result[0]['attributes'][0];
            $actualResult['value'] = $value;

            $expectedResult = [
                'attribute_code' => 'multiselect_attribute',
                'type'  => 'multiselect',
                'value' => 'Option 1',
            ];

            $this->assertEquals($expectedResult, $actualResult);
            $this->assertEquals('simple_with_multiselect', $result[0]['sku']);
        }
    }

    /**
     * Test image attribute
     *
     * @magentoApiDataFixture Magento_CatalogExport::Test/Api/_files/one_product_simple_with_image_attribute.php
     */
    public function testImageAttribute()
    {
        $result = $this->getProductApiResult('simple_with_image');
        if ($this->hasAttributeData($result)) {
            $value = $result[0]['attributes'][0]['value'][0];
            unset($result[0]['attributes'][0]['value']); // re adding as array instead of json
            $actualResult = $result[0]['attributes'][0];
            $actualResult['value'] = $value;

            $expectedResult = [
                'attribute_code' => 'image_attribute',
                'type'  => 'media_image',
                'value' => 'imagepath',
            ];

            $this->assertEquals($expectedResult, $actualResult);
            $this->assertEquals('simple_with_image', $result[0]['sku']);
        }
    }

    /**
     * Test decimal attribute
     *
     * @magentoApiDataFixture Magento_CatalogExport::Test/Api/_files/one_product_simple_with_decimal_attribute.php
     */
    public function testDecimalAttribute()
    {
        $result = $this->getProductApiResult('simple_with_decimal');
        if ($this->hasAttributeData($result)) {
            $value = $result[0]['attributes'][0]['value'][0];
            unset($result[0]['attributes'][0]['value']); // re adding as array instead of json
            $actualResult = $result[0]['attributes'][0];
            $actualResult['value'] = $value;

            $expectedResult = [
                'attribute_code' => 'decimal_attribute',
                'type'  => 'price',
                'value' => '100.000000',
            ];

            $this->assertEquals($expectedResult, $actualResult);
            $this->assertEquals('simple_with_decimal', $result[0]['sku']);
        }
    }

    /**
     * Test text editor attribute
     *
     * @magentoApiDataFixture Magento_CatalogExport::Test/Api/_files/one_product_simple_with_text_editor_attribute.php
     */
    public function testTextEditorAttribute()
    {
        $result = $this->getProductApiResult('simple_with_text_editor');
        if ($this->hasAttributeData($result)) {
            $value = $result[0]['attributes'][0]['value'][0];
            unset($result[0]['attributes'][0]['value']); // re adding as array insted of json
            $actualResult = $result[0]['attributes'][0];
            $actualResult['value'] = $value;

            $expectedResult = [
                'attribute_code' => 'text_editor_attribute',
                'type'  => 'textarea',
                'value' => 'text Editor Attribute test',
            ];
            $this->assertEquals($expectedResult, $actualResult);
            $this->assertEquals('simple_with_text_editor', $result[0]['sku']);
        }
    }

    /**
     * Test Date time attribute
     *
     * @magentoApiDataFixture Magento_CatalogExport::Test/Api/_files/one_product_simple_with_date_attribute.php
     */
    public function testDateAttribute()
    {
        $result = $this->getProductApiResult('simple_with_date');
        if ($this->hasAttributeData($result)) {
            $value = $result[0]['attributes'][0]['value'][0];
            unset($result[0]['attributes'][0]['value']); // re adding as array insted of json
            $actualResult = $result[0]['attributes'][0];
            $actualResult['value'] = $value;

            $expectedResult =  [
                'attribute_code' => 'date_attribute',
                'type'  => 'date',
                'value' => date('Y-m-d 00:00:00'),
            ];

            $this->assertEquals($expectedResult, $actualResult);
            $this->assertEquals('simple_with_date', $result[0]['sku']);
        }
    }

    /**
     * @param $sku
     * @return array|bool|float|int|string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductApiResult($sku)
    {
        $this->_markTestAsRestOnly('SOAP will be covered in another test');

        /** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $product = $productRepository->get($sku);

        /** @see \Magento\CatalogExportApi\Api\EntityRequest and \Magento\CatalogExportApi\Api\EntityRequest\Item */
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

        return $this->_webApiCall($this->createServiceInfo);
    }

    /**
     * Check if result has attribute data
     *
     * @param $result
     * @return bool
     */
    public function hasAttributeData($result)
    {
        if (isset($result[0]['attributes'][0]) &&
            isset($result[0]['attributes'][0]['value'][0])) {

            return true;
        }

        return false;
    }
}
