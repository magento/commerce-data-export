<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\SalesOrdersDataExporter\Test\Integration;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\DataExporter\Model\FeedInterface;
use Magento\DataExporter\Model\FeedPool;
use Magento\Eav\Model\AttributeRepository;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Indexer\Model\Indexer;
use Magento\Sales\Model\OrderRepository;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\Framework\Registry;
use RuntimeException;
use Throwable;

/**
 * Abstract class for order feed tests
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class AbstractOrderFeedTest extends TestCase
{
    /**
     * Order feed indexer
     */
    private const ORDER_FEED_INDEXER = 'sales_order_data_exporter_v2';

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
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var FeedInterface
     */
    protected $ordersFeed;

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
     * @inheritDoc
     */
    protected function setUp() : void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->resource = Bootstrap::getObjectManager()->create(ResourceConnection::class);
        $this->connection = $this->resource->getConnection();
        $this->indexer = Bootstrap::getObjectManager()->create(Indexer::class);
        $this->jsonSerializer = Bootstrap::getObjectManager()->create(Json::class);
        $this->orderRepository = Bootstrap::getObjectManager()->create(ProductRepositoryInterface::class);
        $this->storeManager = Bootstrap::getObjectManager()->create(StoreManagerInterface::class);
        $this->ordersFeed = Bootstrap::getObjectManager()->get(FeedPool::class)->getFeed('salesOrders');
        $this->attributeRepository = Bootstrap::getObjectManager()->create(AttributeRepository::class);
        $this->arrayUtils = $objectManager->create(ArrayUtils::class);
        $this->registry = Bootstrap::getObjectManager()->get(Registry::class);
    }

    /**
     * Run the indexer to extract orders data
     *
     * @param array $ids
     * @return void
     *
     * @throws RuntimeException
     */
    protected function runIndexer(array $ids) : void
    {
        try {
            $this->indexer->load(self::ORDER_FEED_INDEXER);
            $this->indexer->reindexList($ids);
        } catch (Throwable $e) {
            throw new RuntimeException('Could not reindex orders data', $e);
        }
    }
}
