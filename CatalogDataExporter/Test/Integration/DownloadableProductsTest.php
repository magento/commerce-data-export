<?php
/**
 * Copyright 2023 Adobe
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

namespace Magento\CatalogDataExporter\Test\Integration;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Type\Simple;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test class for downloadable products export
 */
class DownloadableProductsTest extends AbstractProductTestHelper
{
    /**
     * Validate downloadable products data
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/downloadable_product_with_files_and_sample_url.php
     *
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws \Zend_Db_Statement_Exception
     * @throws \Throwable
     */
    public function testDownloadableProducts() : void
    {
        $skus = ['downloadable-product'];
        $storeViewCodes = ['default', 'custom_store_view_one', 'custom_store_view_two'];
        $expectedProductAttribute = [
            'default' => '{"purchase_separately":true,"samples":[{"label":"Downloadable Product Sample Title","url":"http:\/\/localhost\/downloadable\/download\/sample\/sample_id\/1"}],"links":[{"uid":"ZG93bmxvYWRhYmxlLzE=","label":"Downloadable Product Link","price":0,"number_of_downloads":0,"sample_url":null},{"uid":"ZG93bmxvYWRhYmxlLzI=","label":"Downloadable Product Link","price":0,"number_of_downloads":0,"sample_url":"http:\/\/localhost\/downloadable\/download\/linkSample\/link_id\/2"}]}',
            'custom_store_view_one' => '{"purchase_separately":true,"samples":[{"label":null,"url":"http:\/\/localhost\/downloadable\/download\/sample\/sample_id\/1"}],"links":[{"uid":"ZG93bmxvYWRhYmxlLzE=","label":"Link 0","price":0,"number_of_downloads":0,"sample_url":null},{"uid":"ZG93bmxvYWRhYmxlLzI=","label":"Link 1","price":0,"number_of_downloads":0,"sample_url":"http:\/\/localhost\/downloadable\/download\/linkSample\/link_id\/2"}]}',
            'custom_store_view_two' => '{"purchase_separately":true,"samples":[{"label":null,"url":"http:\/\/localhost\/downloadable\/download\/sample\/sample_id\/1"}],"links":[{"uid":"ZG93bmxvYWRhYmxlLzE=","label":"Link 0","price":0,"number_of_downloads":0,"sample_url":null},{"uid":"ZG93bmxvYWRhYmxlLzI=","label":"Link 1","price":0,"number_of_downloads":0,"sample_url":"http:\/\/localhost\/downloadable\/download\/linkSample\/link_id\/2"}]}'
        ];

        foreach ($skus as $sku) {
            foreach ($storeViewCodes as $storeViewCode) {
                $store = $this->storeManager->getStore($storeViewCode);
                $product = $this->productRepository->get($sku, false, $store->getId());
                $product->setTypeInstance(Bootstrap::getObjectManager()->create(Simple::class));
                $attribute = [ 'ac_downloadable' => [
                            'attributeCode' => 'ac_downloadable',
                            'value' => [$expectedProductAttribute[$storeViewCode]]
                ]];

                $extractedProduct = $this->getExtractedProduct($sku, $storeViewCode);
                $this->validateBaseProductData($product, $extractedProduct, $storeViewCode);
                $this->validatePricingData($extractedProduct);
                $this->validateImageUrls($product, $extractedProduct);
                $this->validateAttributeData($product, $extractedProduct, $attribute);
                $this->validateMediaGallery($product, $extractedProduct);
                $this->validateVideoData($product, $extractedProduct);
                $this->validateImageData($product, $extractedProduct);
                $this->validateDownloadableData($product, $extractedProduct);
            }
        }
    }

    /**
     * @param ProductInterface $product
     * @param array $extractedProduct
     * @return void
     */
    private function validateDownloadableData(ProductInterface $product, array $extractedProduct): void
    {
        /** @var \Magento\Downloadable\Model\Link[] $downloadableLinks */
        $downloadableLinks = $product->getDownloadableLinks();

        foreach ($downloadableLinks as $link) {
            foreach ($extractedProduct['feedData']['optionsV2'][0]['values'] as $extractedLink) {
                if ($extractedLink['id'] === base64_encode("downloadable/" . $link->getId())) {
                    self::assertEquals($link->getSortOrder(), $extractedLink['sortOrder']);
                    self::assertEquals($link->getTitle(), $extractedLink['label']);
                    self::assertEquals($link->getPrice(), $extractedLink['price']);
                }
            }
        }
        /** @var \Magento\Downloadable\Model\ResourceModel\Sample\Collection $downloadableSamples */
        $downloadableSamples = $product->getDownloadableSamples();
        /** @var \Magento\Downloadable\Model\Sample $sample */
        foreach ($downloadableSamples as $sample) {
            foreach ($extractedProduct['feedData']['samples'] as $extractedSample) {
                $idMatches = [];
                \preg_match("#\d+$#", $extractedSample['resource']['url'], $idMatches);
                if ($idMatches[0] === $sample->getId()) {
                    self::assertEquals($sample->getSortOrder(), $extractedSample['sortOrder']);
                    self::assertNotEmpty($extractedSample['resource']['url']);
                    self::assertEquals($sample->getTitle(), $extractedSample['resource']['label']);
                    //what is "Roles"? TODO: fulfill roles for downloadable product sample and verify it
                    //self::assertEquals($sample->getRoles(), $extractedSample['samples'][$index]['resource']['roles']);
                }
            }
        }
    }
}
