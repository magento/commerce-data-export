<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Api;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\DataExporter\Model\FeedInterface;
use Magento\DataExporter\Model\FeedPool;
use Magento\Framework\Webapi\Rest\Request;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Helper\CompareArraysRecursively;
use Magento\TestFramework\TestCase\WebapiAbstract;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Test to validate product variant exportAPI endpoint
 *
 * @magentoAppIsolation enabled
 */
class ProductVariantExportTest extends WebapiAbstract
{
    /**
     * @var array
     */
    private $createServiceInfo;

    /**
     * @var FeedInterface
     */
    private $productVariantsFeed;

    /**
     * @var CompareArraysRecursively
     */
    private $compareArraysRecursively;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->productVariantsFeed = $objectManager->get(FeedPool::class)->getFeed('variants');
        $this->compareArraysRecursively = $objectManager->create(CompareArraysRecursively::class);
        $this->productRepository = $objectManager->get(ProductRepositoryInterface::class);

        $this->createServiceInfo = [
            'get' => [
                'rest' => [
                    'resourcePath' => '/V1/catalog-export/product-variants',
                    'httpMethod' => Request::HTTP_METHOD_GET,
                ]
            ],
            'getByProductIds' => [
                'rest' => [
                    'resourcePath' => '/V1/catalog-export/product-variants/product-ids',
                    'httpMethod' => Request::HTTP_METHOD_GET,
                ]
            ]
        ];
    }

    /**
     * Test configurable product variant get endpoint
     *
     * @magentoApiDataFixture Magento/ConfigurableProduct/_files/configurable_products_with_two_attributes.php
     * @return void
     * @throws NoSuchEntityException
     */
    public function testGetVariantById(): void
    {
        self::markTestSkipped('Should be migrated to integration test');
        $this->_markTestAsRestOnly('SOAP will be covered in another test');
        $configurable = $this->productRepository->get('configurable');
        $configurableId = $configurable->getId();
        $simple = $this->productRepository->get('simple_10');
        $simpleId = $simple->getId();
        $variantId = \base64_encode(\sprintf(
            'configurable/%1$s/%2$s',
            $configurableId,
            $simpleId,
        ));
        $this->createServiceInfo['get']['rest']['resourcePath'] .= '?ids[0]=' . $variantId;
        $apiResult = $this->_webApiCall($this->createServiceInfo['get'], []);
        $variantFeed = $this->productVariantsFeed->getFeedByIds([$variantId])['feed'];

        foreach (array_keys($variantFeed) as $feedKey) {
            unset($variantFeed[$feedKey]['modifiedAt'], $variantFeed[$feedKey]['deleted']);
        }

        $this->assertVariantsEqual($variantFeed, $apiResult);
    }

    /**
     * Test configurable product variant getByProductId endpoint
     *
     * @magentoApiDataFixture Magento/ConfigurableProduct/_files/configurable_products_with_two_attributes.php
     * @return void
     * @throws NoSuchEntityException
     */
    public function testGetVariantByProductId(): void
    {
        self::markTestSkipped('Should be migrated to integration test');
        $this->_markTestAsRestOnly('SOAP will be covered in another test');

        $product = $this->productRepository->get('configurable');
        $productId = $product->getId();

        $this->createServiceInfo['getByProductIds']['rest']['resourcePath'] .= '?productIds[0]=' . $productId;
        $apiResult = $this->_webApiCall($this->createServiceInfo['getByProductIds'], []);
        $variantFeed = $this->productVariantsFeed->getFeedByProductIds([$productId])['feed'];
        foreach (array_keys($variantFeed) as $feedKey) {
            unset($variantFeed[$feedKey]['modifiedAt'], $variantFeed[$feedKey]['deleted']);
        }

        $this->assertVariantsEqual($variantFeed, $apiResult);
    }

    /**
     * Assert that the arrays returned by feed and exportAPI contain the same variants.
     *
     * @param array $variantFeed
     * @param array $apiResult
     * @return void
     */
    private function assertVariantsEqual(array $variantFeed, array $apiResult): void
    {
        $diff = $this->compareArraysRecursively->execute($variantFeed, $apiResult);
        $this->assertEquals([], $diff, "Actual categories response doesn't equal expected data");
    }
}
