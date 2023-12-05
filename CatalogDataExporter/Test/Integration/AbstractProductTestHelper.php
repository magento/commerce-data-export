<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Integration;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Category as CategoryHelper;
use Magento\Catalog\Helper\Product as ProductHelper;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Indexer\Model\Indexer;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Api\GroupRepositoryInterface;
use Magento\Tax\Model\TaxClass\Source\Product as TaxClassSource;
use Magento\TestFramework\Helper\Bootstrap;
use function PHPUnit\Framework\assertEmpty;

/**
 * Abstract Class AbstractProductTestHelper
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class AbstractProductTestHelper extends \PHPUnit\Framework\TestCase
{
    /**
     * Test Constants
     */
    public const CATALOG_DATA_EXPORTER = 'catalog_data_exporter_products';

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var String
     */
    protected $connection;

    /**
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * @var Indexer
     */
    protected $indexer;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ProductHelper
     */
    protected $productHelper;

    /**
     * @var CategoryHelper
     */
    protected $categoryHelper;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var TaxClassSource
     */
    protected $taxClassSource;

    /**
     * @var StoreRepositoryInterface
     */
    protected $storeRepositoryInterface;

    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepositoryInterface;

    /**
     * @var GroupRepositoryInterface|mixed
     */
    private $storeGroupRepositoryInterface;

    /**
     * Setup tests
     */
    protected function setUp(): void
    {
        $this->resource = Bootstrap::getObjectManager()->create(ResourceConnection::class);
        $this->connection = $this->resource->getConnection();
        $this->indexer = Bootstrap::getObjectManager()->create(Indexer::class);

        $this->categoryRepository = Bootstrap::getObjectManager()->create(CategoryRepositoryInterface::class);
        $this->categoryHelper = Bootstrap::getObjectManager()->create(CategoryHelper::class);

        $this->productRepository = Bootstrap::getObjectManager()->create(ProductRepositoryInterface::class);
        $this->productHelper = Bootstrap::getObjectManager()->create(ProductHelper::class);
        $this->storeManager = Bootstrap::getObjectManager()->create(StoreManagerInterface::class);
        $this->storeRepositoryInterface = Bootstrap::getObjectManager()->create(StoreRepositoryInterface::class);
        $this->storeGroupRepositoryInterface = Bootstrap::getObjectManager()->create(GroupRepositoryInterface::class);
        $this->websiteRepositoryInterface = Bootstrap::getObjectManager()->create(WebsiteRepositoryInterface::class);
        $this->taxClassSource = Bootstrap::getObjectManager()->create(TaxClassSource::class);

        $this->jsonSerializer = Bootstrap::getObjectManager()->create(Json::class);

        $this->indexer->load(self::CATALOG_DATA_EXPORTER);
        $this->reindexProductDataExporter();
    }

    /**
     * Get the extracted product data stored in the catalog extract table
     *
     * @param string $sku
     * @param string $storeViewCode
     * @return array
     */
    protected function getExtractedProduct(string $sku, string $storeViewCode) : array
    {
        // Select data from exporter table
        $query = $this->connection->select()
            ->from(['ex' => $this->resource->getTableName(self::CATALOG_DATA_EXPORTER)])
            ->where('ex.sku = ?', $sku)
            ->where('ex.store_view_code = ?', $storeViewCode);
        $cursor = $this->connection->query($query);
        $data = [];
        while ($row = $cursor->fetch()) {
            $data[$row['sku']]['sku'] = $row['sku'];
            $data[$row['sku']]['store_view_code'] = $row['store_view_code'];
            $data[$row['sku']]['modified_at'] = $row['modified_at'];
            $data[$row['sku']]['is_deleted'] = $row['is_deleted'];
            $data[$row['sku']]['feedData'] = $this->jsonSerializer->unserialize($row['feed_data']);
        }

        return !empty($data[$sku]) ? $data[$sku] : $data;
    }

    /**
     * Run partial exported indexer
     *
     * @param array $ids
     * @return void
     */
    protected function emulatePartialReindexBehavior(array $ids = []) : void
    {
        $this->indexer->reindexList($ids);
    }

    /**
     * Reindex all the product data exporter table for existing products
     *
     * @return void
     */
    private function reindexProductDataExporter() : void
    {
        $searchCriteria = Bootstrap::getObjectManager()->create(SearchCriteriaInterface::class);

        $productIds = array_map(function ($product) {
            return $product->getId();
        }, $this->productRepository->getList($searchCriteria)->getItems());

        if (!empty($productIds)) {
            $this->indexer->reindexList($productIds);
        }
    }

    /**
     * Validate pricing data in extracted product data
     *
     * @param array $extractedProduct
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws \Zend_Db_Statement_Exception
     */
    protected function validatePricingData(array $extractedProduct) : void
    {
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrency()->getCode();

        $this->assertEquals($currencyCode, $extractedProduct['feedData']['currency']);
    }

    /**
     * Validate category data in extracted product data
     *
     * @param ProductInterface $product
     * @param array $extractedProduct
     * @return void
     * @throws NoSuchEntityException
     */
    protected function validateCategoryData(ProductInterface $product, array $extractedProduct, $storeViewCode) : void
    {
        // Disabled product does not have information about assigned entities since we got this from index table
        if ($product->getStatus() == Status::STATUS_DISABLED) {
            return ;
        }
        $storeViewId = $this->storeRepositoryInterface->get($storeViewCode)->getCode();
        $storeId = $this->storeManager->getStore($storeViewId)->getId();
        $categories = [];
        foreach ($product->getCategoryIds() as $categoryId) {
            $category = $this->categoryRepository->get($categoryId, $storeId);
            $parentId = $category->getParentId();
            $path = $category->getUrlKey();
            while ($parentId) {
                $parent = $this->categoryRepository->get($parentId, $storeId);
                // show only storefront-visible categories
                if ($parent->getLevel() < 2) {
                    break;
                }
                $parentId = $parent->getParentId();
                $urlKey = $parent->getUrlKey();
                if ($urlKey) {
                    $path = $urlKey . '/' . $path;
                }
            }
            $categories[] = $path;
        }

         $this->assertEquals($categories, $extractedProduct['feedData']['categories']);
    }

    /**
     * Validate base product data in extracted product data
     *
     * @param ProductInterface $product
     * @param array $extract
     * @param string $storeViewCode
     * @return void
     * @throws LocalizedException
     */
    protected function validateBaseProductData(ProductInterface $product, array $extract, string $storeViewCode) : void
    {
        $this->assertNotEmpty($extract, "Exported Product Data is empty");

        $storeViewId = $this->storeRepositoryInterface->get($storeViewCode)->getCode();
        $storeView = $this->storeManager->getStore($storeViewId);
        $websiteCode = $this->websiteRepositoryInterface->getById($storeView->getWebsiteId())->getCode();
        $storeGroupCode = $this->storeGroupRepositoryInterface->get($storeView->getStoreGroupId())->getCode();
        $enabled = $product->getStatus() == 1 ? 'Enabled' : 'Disabled';
        $visibility = Visibility::getOptionText($product->getVisibility());

        $this->assertEquals($product->getSku(), $extract['sku']);
        $this->assertEquals($product->getSku(), $extract['feedData']['sku']);
        $this->assertEquals($product->getId(), $extract['feedData']['productId']);
        $this->assertEquals($websiteCode, $extract['feedData']['websiteCode']);
        $this->assertEquals($storeGroupCode, $extract['feedData']['storeCode']);
        $this->assertEquals($storeViewCode, $extract['feedData']['storeViewCode']);
        $this->assertEquals($product->getName(), $extract['feedData']['name']);
        $this->assertEquals($enabled, $extract['feedData']['status']);
        $this->assertEquals($product->getId(), $extract['feedData']['productId']);
        $this->assertEquals($product->getTypeId(), $extract['feedData']['type']);

        if ($product->getUrlKey()) {
            $this->assertEquals($product->getUrlKey(), $extract['feedData']['urlKey']);
        }

        $this->assertEquals($product->getCreatedAt(), $extract['feedData']['createdAt']);
        $this->assertEquals($product->getUpdatedAt(), $extract['feedData']['updatedAt']);
        $this->assertEquals($product->getDescription(), $extract['feedData']['description']);
        $this->assertEquals($product->getMetaDescription(), $extract['feedData']['metaDescription']);
        $this->assertEquals($product->getMetaKeyword(), $extract['feedData']['metaKeyword']);
        $this->assertEquals($product->getMetaTitle(), $extract['feedData']['metaTitle']);
        $this->assertEquals($product->getTaxClassId(), $extract['feedData']['taxClassId']);
        $this->assertEquals($visibility, $extract['feedData']['visibility']);
    }

    /**
     * Validate base product data in extracted product data
     *
     * @param ProductInterface $product
     * @param array $extract
     * @return void
     */
    protected function validateRealProductData(ProductInterface $product, array $extract) : void
    {
        $this->assertEquals($product->getWeight(), $extract['feedData']['weight']);
    }

    /**
     * Validate product image URLs in extracted product data
     *
     * @param ProductInterface $product
     * @param array $extractedProduct
     * @return void
     *
     * @deprecated "role-based" images will be removed from product level
     */
    protected function validateImageUrls(ProductInterface $product, array $extractedProduct) : void
    {
        $feedData = $extractedProduct['feedData'];

        $this->assertEquals($this->productHelper->getImageUrl($product), $feedData['image']['url']);
        $this->assertEquals($this->productHelper->getSmallImageUrl($product), $feedData['smallImage']['url']);
        $this->assertEquals($this->productHelper->getThumbnailUrl($product), $feedData['thumbnail']['url']);
    }

    /**
     * Validate product attributes in extracted product data
     *
     * @param ProductInterface $product
     * @param array $extractedProduct
     * @return void
     */
    protected function validateAttributeData(ProductInterface $product, array $extractedProduct) : void
    {
        $customLabel = $product->getCustomAttribute('custom_label');
        $customDescription = $product->getCustomAttribute('custom_description');
        $customSelect = $product->getCustomAttribute('custom_select');
        $yesNo = $product->getCustomAttribute('yes_no_attribute');

        $attributes = null;
        if ($customLabel) {
            $attributes[$customLabel->getAttributeCode()] = [
                'attributeCode' => $customLabel->getAttributeCode(),
                'value' => [$customLabel->getValue()],
                'valueId' => null
            ];
        }
        if ($customDescription) {
            $attributes[$customDescription->getAttributeCode()] = [
                'attributeCode' => $customDescription->getAttributeCode(),
                'value' => [$customDescription->getValue()],
                'valueId' => null
            ];
        }
        if ($customSelect) {
            $attributes[$customSelect->getAttributeCode()] = [
                'attributeCode' => $customSelect->getAttributeCode(),
                'value' => [$product->getAttributeText('custom_select')],
                'valueId' => [$product->getData('custom_select')]
            ];
        }
        if ($yesNo) {
            $yesNoValues = [0 => 'no', 1 => 'yes'];
            $yesNoActualValue = $product->getData('yes_no_attribute');
            $attributes[$yesNo->getAttributeCode()] = [
                'attributeCode' => $yesNo->getAttributeCode(),
                'value' => [$yesNoValues[$yesNoActualValue] ?? null],
                'valueId' => [$yesNoActualValue]
            ];
        }
        $feedAttributes = null;
        if (isset($extractedProduct['feedData']['attributes'])) {
            $feedAttributesData = $extractedProduct['feedData']['attributes'];
            foreach ($feedAttributesData as $feed) {
                $feedAttributes[$feed['attributeCode']] = [
                    'attributeCode' => $feed['attributeCode'],
                    'value' => $feed['value'],
                    'valueId' => $feed['valueId'],
                ];
            }
        }

        $this->assertEquals($attributes, $feedAttributes);
    }

    /**
     * Validate product media gallery data in extracted product
     *
     * @param ProductInterface $product
     * @param array $extractedProduct
     *
     * @return void
     *
     * @throws NoSuchEntityException
     *
     * @deprecated use validateVideoData / validateImageData
     */
    protected function validateMediaGallery(ProductInterface $product, array $extractedProduct) : void
    {
        if ($product->getSku() === 'simple1' || $product->getSku() === 'simple2') {
            $productGalleryEntries = $product->getMediaGalleryEntries();
            $this->assertCount(\count($productGalleryEntries), $extractedProduct['feedData']['media_gallery']);

            $galleryEntry = \array_shift($productGalleryEntries);
            $extensionAttributes = $galleryEntry->getExtensionAttributes();
            $mediaBaseUrl = $this->storeManager->getStore($extractedProduct['feedData']['storeViewCode'])
                ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

            $expectedResult = [
                'url' => $mediaBaseUrl . 'catalog/product' . $galleryEntry->getFile(),
                'label' => $galleryEntry->getLabel() ?: '',
                'types' => $galleryEntry->getTypes() ?: null,
                'sort_order' => (int)$galleryEntry->getPosition(),
            ];

            if (null !== $extensionAttributes && $extensionAttributes->getVideoContent()) {
                $expectedResult['video_attributes'] = [
                    'mediaType' => $extensionAttributes->getVideoContent()->getMediaType(),
                    'videoUrl' => $extensionAttributes->getVideoContent()->getVideoUrl(),
                    'videoProvider' => $extensionAttributes->getVideoContent()->getVideoProvider(),
                    'videoTitle' => $extensionAttributes->getVideoContent()->getVideoTitle(),
                    'videoDescription' => $extensionAttributes->getVideoContent()->getVideoDescription(),
                    'videoMetadata' => $extensionAttributes->getVideoContent()->getVideoMetadata(),
                ];
            }

            $this->assertEquals($expectedResult, \array_shift($extractedProduct['feedData']['media_gallery']));
        } else {
            $this->assertNull($extractedProduct['feedData']['media_gallery']);
        }
    }

    /**
     * Validate product video data in extracted product
     *
     * @param ProductInterface $product
     * @param array $extractedProduct
     *
     * @return void
     *
     * @throws NoSuchEntityException
     */
    protected function validateVideoData(ProductInterface $product, array $extractedProduct) : void
    {
        if ($product->getSku() === 'simple1') {
            $productGalleryEntries = $product->getMediaGalleryEntries();

            $this->assertCount(\count($productGalleryEntries), $extractedProduct['feedData']['videos']);

            $galleryEntry = \array_shift($productGalleryEntries);
            $mediaBaseUrl = $this->storeManager->getStore($extractedProduct['feedData']['storeViewCode'])
                ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

            $expectedResult = [
                'preview' => [
                    'url' => $mediaBaseUrl . 'catalog/product' . $galleryEntry->getFile(),
                    'label' => $galleryEntry->getLabel() ?: '',
                    'roles' => $galleryEntry->getTypes() ?: null,
                ],
                'sortOrder' => (int)$galleryEntry->getPosition(),
            ];

            $extensionAttributes = $galleryEntry->getExtensionAttributes();
            if (null !== $extensionAttributes && $extensionAttributes->getVideoContent()) {
                $expectedResult['video'] = [
                    'videoProvider' => $extensionAttributes->getVideoContent()->getVideoProvider(),
                    'videoUrl' => $extensionAttributes->getVideoContent()->getVideoUrl(),
                    'videoTitle' => $extensionAttributes->getVideoContent()->getVideoTitle(),
                    'videoDescription' => $extensionAttributes->getVideoContent()->getVideoDescription(),
                    'videoMetadata' => $extensionAttributes->getVideoContent()->getVideoMetadata(),
                    'mediaType' => $extensionAttributes->getVideoContent()->getMediaType(),
                ];
            }

            $this->assertEquals($expectedResult, \array_shift($extractedProduct['feedData']['videos']));
        } else {
            $this->assertNull($extractedProduct['feedData']['videos']);
        }
    }

    /**
     * Validate product image data in extracted product
     *
     * @param ProductInterface $product
     * @param array $extractedProduct
     *
     * @return void
     *
     * @throws NoSuchEntityException
     */
    protected function validateImageData(ProductInterface $product, array $extractedProduct) : void
    {
        if ($product->getSku() === 'simple2') {
            $productGalleryEntries = $product->getMediaGalleryEntries();

            $this->assertCount(\count($productGalleryEntries), $extractedProduct['feedData']['images']);

            $galleryEntry = \array_shift($productGalleryEntries);
            $mediaBaseUrl = $this->storeManager->getStore($extractedProduct['feedData']['storeViewCode'])
                ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

            $expectedResult = [
                'resource' => [
                    'url' => $mediaBaseUrl . 'catalog/product' . $galleryEntry->getFile(),
                    'label' => $galleryEntry->getLabel() ?: '',
                    'roles' => $galleryEntry->getTypes() ?: null,
                ],
                'sortOrder' => (int)$galleryEntry->getPosition(),
            ];

            $this->assertEquals($expectedResult, \array_shift($extractedProduct['feedData']['images']));
        } else {
            $this->assertNull($extractedProduct['feedData']['images']);
        }
    }

    /**
     * Wait one second before test execution after fixtures created.
     *
     * @return void
     */
    protected function emulateCustomersBehaviorAfterDeleteAction(): void
    {
        // Avoid getFeed right after product was created.
        // We have to emulate real customers behavior
        // as it's possible that feed in test will be retrieved in same time as product created:
        // \Magento\DataExporter\Model\Query\RemovedEntitiesByModifiedAtQuery::getQuery
        sleep(1);
    }

    /**
     * @param string $sku
     * @return int|null
     * @throws NoSuchEntityException
     */
    public function getProductId(string $sku): ?int
    {
        $product = $this->productRepository->get($sku);
        return (int)$product->getId();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->truncateProductDataExporterIndexTable();
    }

    /**
     * Truncates index table
     */
    private function truncateProductDataExporterIndexTable(): void
    {
        $connection = $this->resource->getConnection();
        $feedTable = $this->resource->getTableName(self::CATALOG_DATA_EXPORTER);
        $connection->truncateTable($feedTable);
    }
}
