<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogExport\Test\Api;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\DataExporter\Model\FeedInterface;
use Magento\DataExporter\Model\FeedPool;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Indexer\Model\Indexer;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\WebapiAbstract;
use Magento\TestFramework\Helper\CompareArraysRecursively;

/**
 * Class AbstractProductExportTestHelper
 *
 * @magentoAppIsolation enabled
 */
abstract class AbstractProductExportTestHelper extends WebapiAbstract
{
    /**
     * @var array
     */
    protected $createServiceInfo;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var CompareArraysRecursively
     */
    private $compareArraysRecursively;

    /**
     * @var FeedInterface
     */
    protected $productsFeed;

    /**
     * @var Indexer
     */
    private $indexer;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var string[]
     */
    protected $attributesToCompare = [
    ];

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->productsFeed = $this->objectManager->get(FeedPool::class)->getFeed('products');

        $this->indexer = Bootstrap::getObjectManager()->create(Indexer::class);
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->compareArraysRecursively = $this->objectManager->create(CompareArraysRecursively::class);

        $this->createServiceInfo = [
            'rest' => [
                'resourcePath' => '/V1/catalog-export/products',
                'httpMethod' => Request::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => 'catalogExportApiProductRepositoryV1',
                'serviceVersion' => 'V1',
                'operation' => 'catalogExportApiProductRepositoryV1Get',
            ],
        ];
    }

    /**
     * Validate product data
     *
     * @param array $expected
     * @param array $actual
     * @return void
     */
    protected function assertProductsEquals(array $expected, array $actual): void
    {

        foreach ($expected as $key => $product) {
            foreach (array_keys($product) as $attribute) {
                if (!array_contains($this->attributesToCompare, $key)) {
                    unset($expected[$key][$attribute]);
                }
            }
        }

        $diff = $this->compareArraysRecursively->execute(
            $this->camelToSnakeCaseRecursive($expected),
            $actual
        );
        self::assertEquals([], $diff, "Actual response doesn't equal expected data");
    }

    /**
     * Transform camel case to snake case
     *
     * @param string|int $string
     * @return string string
     */
    private function camelToSnakeCase($string): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $string));
    }

    /**
     * Transform camel case to snake case recursively
     *
     * @param array|mixed $data
     * @return array|mixed
     */
    private function camelToSnakeCaseRecursive($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                unset($data[$key]);
                $data[$this->camelToSnakeCase($key)] = $this->camelToSnakeCaseRecursive($value);
            }
        }
        return $data;
    }

    /**
     * Run the indexer to extract product data
     *
     * @return void
     */
    protected function runIndexer(): void
    {
        try {
            $this->indexer->load('catalog_data_exporter_products');
            $this->indexer->reindexAll();
        } catch (\Throwable $e) {
            $this->fail("Couldn`t run catalog_data_exporter_products reindex" . $e->getMessage());
        }
    }
}
