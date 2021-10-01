<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Test\Integration;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ProductVariantDataExporter\Model\Provider\ProductVariants\ConfigurableId;
use Magento\DataExporter\Model\FeedInterface;
use Magento\DataExporter\Model\FeedPool;
use Magento\Eav\Model\AttributeRepository;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Indexer\Model\Indexer;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\Framework\Registry;
use RuntimeException;
use Throwable;

/**
 * Abstract class for product variant feed tests
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class AbstractProductVariantsTest extends TestCase
{
    /**
     * Product variant feed indexer
     */
    private const PRODUCT_VARIANT_FEED_INDEXER = 'catalog_data_exporter_product_variants';

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var Indexer
     */
    protected $indexer;

    /**
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var FeedInterface
     */
    protected $productVariantsFeed;

    /**
     * @var AttributeRepository
     */
    protected $attributeRepository;

    /**
     * @var ArrayUtils
     */
    protected $arrayUtils;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var ConfigurableId|mixed
     */
    protected $idResolver;

    /**
     * @inheritDoc
     */
    protected function setUp() : void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->resource = Bootstrap::getObjectManager()->create(ResourceConnection::class);
        $this->connection = $this->resource->getConnection();
        $this->indexer = Bootstrap::getObjectManager()->create(Indexer::class);
        $this->jsonSerializer = Bootstrap::getObjectManager()->create(Json::class);
        $this->productRepository = Bootstrap::getObjectManager()->create(ProductRepositoryInterface::class);
        $this->storeManager = Bootstrap::getObjectManager()->create(StoreManagerInterface::class);
        $this->productVariantsFeed = Bootstrap::getObjectManager()->get(FeedPool::class)->getFeed('variants');
        $this->attributeRepository = Bootstrap::getObjectManager()->create(AttributeRepository::class);
        $this->arrayUtils = $objectManager->create(ArrayUtils::class);
        $this->registry = Bootstrap::getObjectManager()->get(Registry::class);
        $this->idResolver = Bootstrap::getObjectManager()->get(ConfigurableId::class);
    }

    /**
     * Run the indexer to extract product variants data
     *
     * @param array $parentIds
     * @return void
     *
     * @throws RuntimeException
     */
    protected function runIndexer(array $parentIds) : void
    {
        try {
            $this->indexer->load(self::PRODUCT_VARIANT_FEED_INDEXER);
            $this->indexer->reindexList($parentIds);
        } catch (Throwable $e) {
            throw new RuntimeException('Could not reindex product variant data');
        }
    }
}
