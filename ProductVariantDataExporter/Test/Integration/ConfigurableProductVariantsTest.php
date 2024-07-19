<?php
/**
 * Copyright 2024 Adobe
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

namespace Magento\ProductVariantDataExporter\Test\Integration;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\ProductVariantDataExporter\Model\Provider\ProductVariants\ConfigurableId;
use RuntimeException;
use Throwable;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Action;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test class for configurable product variants export
 */
class ConfigurableProductVariantsTest extends AbstractProductVariantsTest
{
    /**
     * @var string
     */
    private const EXPECTED_DATE_TIME_FORMAT = '%d-%d-%d %d:%d:%d';

    /**
     * Test configurable product variants.
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture Magento/ConfigurableProduct/_files/configurable_products_with_two_attributes.php
     * @return void
     * @throws NoSuchEntityException
     */
    public function testConfigurableVariants(): void
    {
        $configurable = $this->productRepository->get('configurable');
        $this->runIndexer([$configurable->getId()]);
        try {
            $expected = $this->getExpectedProductVariants(['simple_10', 'simple_20']);

            $variantSimple10 = $this->idResolver->resolve([
                ConfigurableId::PARENT_SKU_KEY => 'configurable',
                ConfigurableId::CHILD_SKU_KEY => 'simple_10'
            ]);
            $variantSimple20 = $this->idResolver->resolve([
                ConfigurableId::PARENT_SKU_KEY => 'configurable',
                ConfigurableId::CHILD_SKU_KEY => 'simple_20'
            ]);
            $actual = $this->getVariantByIds([$variantSimple10, $variantSimple20]);
            $modifiedAt = (new \DateTime())->getTimestamp();
            foreach ($actual as $actualFeed) {
                $this->assertNotEmpty($actualFeed['modifiedAt']);
                $this->assertStringMatchesFormat(self::EXPECTED_DATE_TIME_FORMAT, $actualFeed['modifiedAt']);
                $actualModifiedAt = (new \DateTime($actualFeed['modifiedAt']))->getTimestamp();
                $this->assertEqualsWithDelta($modifiedAt, $actualModifiedAt, 3);
            }
            $diff = $this->arrayUtils->recursiveDiff($expected, $actual);
            self::assertEquals([], $diff, "Product variants response doesn't equal expected response");
        } catch (Throwable $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * Test that variants are deleted as expected.
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/ConfigurableProduct/_files/product_configurable.php
     *
     * @return void
     */
    public function testDeleteConfigurableProductVariants(): void
    {
        try {
            $configurable = $this->productRepository->get('configurable');
            $configurableId = $configurable->getId();
            $this->runIndexer([$configurableId]);
            $variantSimple10 = $this->idResolver->resolve([
                ConfigurableId::PARENT_SKU_KEY => 'configurable',
                ConfigurableId::CHILD_SKU_KEY => 'simple_10'
            ]);
            $variantSimple20 = $this->idResolver->resolve([
                ConfigurableId::PARENT_SKU_KEY => 'configurable',
                ConfigurableId::CHILD_SKU_KEY => 'simple_20'
            ]);
            $realVariantsData = $this->getVariantByIds(
                [$variantSimple10, $variantSimple20]
            );
            $this->assertCount(2, $realVariantsData);

            $simple = $this->productRepository->get('simple_10');
            $simpleId  = $simple->getId();
            $this->deleteProduct($simple->getSku());
            $this->emulateCustomersBehaviorAfterDeleteAction();
            $this->runIndexer([$configurableId, $simpleId]);

            $assignedVariantsData = $this->getVariantByIds([$variantSimple10, $variantSimple20]);
            $this->assertCount(2, $assignedVariantsData); //id20, id10 (deleted)
            $this->assertEquals('simple_10', $assignedVariantsData[0]['productSku']);
            $this->assertTrue($assignedVariantsData[0]['deleted']);

            $this->assertEquals('simple_20', $assignedVariantsData[1]['productSku']);
            $this->assertFalse($assignedVariantsData[1]['deleted']);
        } catch (Throwable $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * Test that variants are removed from links to configurable (but product is not deleted)
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/ConfigurableProduct/_files/product_configurable.php
     *
     * @return void
     */
    public function testUnassignedChildFromConfigurableProductVariants(): void
    {
        try {
            $configurable = $this->productRepository->get('configurable');
            $configurableId = $configurable->getId();

            $simple10 = $this->productRepository->get('simple_10');
            $simple20 = $this->productRepository->get('simple_20');

            $variantSimple10 = $this->idResolver->resolve([
                ConfigurableId::PARENT_SKU_KEY => 'configurable',
                ConfigurableId::CHILD_SKU_KEY => 'simple_10'
            ]);
            $variantSimple20 = $this->idResolver->resolve([
                ConfigurableId::PARENT_SKU_KEY => 'configurable',
                ConfigurableId::CHILD_SKU_KEY => 'simple_20'
            ]);

            $this->runIndexer([$configurableId]);

            $variantsData = $this->getVariantByIds([$variantSimple10, $variantSimple20], true);
            $this->assertCount(2, $variantsData); //id20, id10

            // we remove simple_10 from associated products of configurable
            $associatedProductIds[] = $simple20->getId();
            $extensionConfigurableAttributes = $configurable->getExtensionAttributes();
            $extensionConfigurableAttributes->setConfigurableProductLinks($associatedProductIds);
            $configurable->setExtensionAttributes($extensionConfigurableAttributes);
            $this->productRepository->save($configurable);
            $this->emulateCustomersBehaviorAfterDeleteAction();
            $this->runIndexer([$configurableId, $simple10->getId()]);

            $variantsData = $this->getVariantByIds([$variantSimple10, $variantSimple20]);
            $this->assertCount(2, $variantsData); //id20, id10 (deleted)
            $this->assertEquals('simple_10', $variantsData[0]['productSku']);
            $this->assertTrue($variantsData[0]['deleted']);

            $this->assertEquals('simple_20', $variantsData[1]['productSku']);
            $this->assertFalse($variantsData[1]['deleted']);
        } catch (Throwable $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * Test that product variant can be disabled and re-enabled
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/ConfigurableProduct/_files/product_configurable_sku.php
     *
     * @return void
     * @throws NoSuchEntityException
     * @throws \Zend_Db_Statement_Exception
     */
    public function testDisabledAndReanableChildFromConfigurableProductVariants(): void
    {
        $configurable = $this->productRepository->get('configurable');
        $configurableId = $configurable->getId();
        $simple10 = $this->productRepository->get('simple_10');

        $variantSimple10 = $this->idResolver->resolve([
            ConfigurableId::PARENT_SKU_KEY => 'configurable',
            ConfigurableId::CHILD_SKU_KEY => 'simple_10'
        ]);
        $variantSimple20 = $this->idResolver->resolve([
            ConfigurableId::PARENT_SKU_KEY => 'configurable',
            ConfigurableId::CHILD_SKU_KEY => 'simple_20'
        ]);

        // initiate feed state
        $this->runIndexer([$configurableId]);

        // disable variant && verify "deleted" is set to true
        $this->changeProductStatusProduct('simple_10', Status::STATUS_DISABLED);
        $this->runIndexer([$simple10->getId()]);

        $variantsData = $this->getVariantByIds([$variantSimple10, $variantSimple20]);
        $this->assertCount(2, $variantsData); //id20, id10 (disabled)
        $this->assertEquals('simple_10', $variantsData[0]['productSku']);
        $this->assertFalse($variantsData[0]['deleted'], "simple_10 should not have been flag as deleted");

        $this->assertEquals('simple_20', $variantsData[1]['productSku']);
        $this->assertFalse($variantsData[1]['deleted'], "simple_20 should not not have been flag as deleted");

        // enable variant && verify "deleted" is set to false
        // verify enabling child product back works
        $this->changeProductStatusProduct('simple_10', Status::STATUS_ENABLED);
        $this->runIndexer([$simple10->getId()]);

        $variantsData = $this->getVariantByIds([$variantSimple10, $variantSimple20]);
        $this->assertCount(2, $variantsData); //id20, id10 (re-enabled)
        $this->assertEquals('simple_10', $variantsData[0]['productSku']);
        $this->assertFalse($variantsData[0]['deleted'], "simple_10 should not have flag 'deleted'");
    }

    /**
     * Test that product virtual variant can be disabled and re-enabled
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento_ProductVariantDataExporter::Test/_files/product_configurable_with_virtual_sku.php
     *
     * @return void
     * @throws NoSuchEntityException
     * @throws \Zend_Db_Statement_Exception
     */
    public function testDisabledAndReanableVirtualChildFromConfigurableProductVariants(): void
    {
        $configurable = $this->productRepository->get('configurable');
        $configurableId = $configurable->getId();

        $virtual10 = $this->productRepository->get('virtual_10');

        $variantVirtual10 = $this->idResolver->resolve([
            ConfigurableId::PARENT_SKU_KEY => 'configurable',
            ConfigurableId::CHILD_SKU_KEY => 'virtual_10'
        ]);
        $variantVirtual20 = $this->idResolver->resolve([
            ConfigurableId::PARENT_SKU_KEY => 'configurable',
            ConfigurableId::CHILD_SKU_KEY => 'virtual_20'
        ]);

        // initiate feed state
        $this->runIndexer([$configurableId]);

        // disable variant && verify "deleted" is set to true
        $this->changeProductStatusProduct('virtual_10', Status::STATUS_DISABLED);
        $this->runIndexer([$virtual10->getId()]);

        $variantsData = $this->getVariantByIds([$variantVirtual10, $variantVirtual20]);
        $this->assertCount(2, $variantsData); //id20, id10 (disabled)
        $this->assertEquals('virtual_10', $variantsData[0]['productSku']);
        $this->assertFalse($variantsData[0]['deleted'], "virtual_10 should not have been flag as deleted");

        $this->assertEquals('virtual_20', $variantsData[1]['productSku']);
        $this->assertFalse($variantsData[1]['deleted'], "virtual_20 should not not have been flag as deleted");

        // enable variant && verify "deleted" is set to false
        // verify enabling child product back works
        $this->changeProductStatusProduct('virtual_10', Status::STATUS_ENABLED);
        $this->runIndexer([$virtual10->getId()]);

        $variantsData = $this->getVariantByIds([$variantVirtual10, $variantVirtual20]);
        $this->assertCount(2, $variantsData); //id20, id10 (re-enabled)
        $this->assertEquals('virtual_10', $variantsData[0]['productSku']);
        $this->assertFalse($variantsData[0]['deleted'], "virtual_10 should not have flag 'deleted'");
    }

    /**
     * Test that disabled product variant is exported in feed
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/ConfigurableProduct/_files/product_configurable_sku.php
     *
     * @return void
     */
    public function testDisabledVariantIsStillExportedInFeed(): void
    {
        try {
            $configurable = $this->productRepository->get('configurable');
            $configurableId = $configurable->getId();

            $simple10 = $this->productRepository->get('simple_10');

            $variantSimple10 = $this->idResolver->resolve([
                ConfigurableId::PARENT_SKU_KEY => 'configurable',
                ConfigurableId::CHILD_SKU_KEY => 'simple_10'
            ]);

            // disable variant and run indexer
            $this->changeProductStatusProduct('simple_10', Status::STATUS_DISABLED);
            $this->runIndexer([$configurableId, $simple10->getId()]);

            $variantsData = $this->getVariantByIds([$variantSimple10]);
            $this->assertCount(1, $variantsData);
            $this->assertEquals('simple_10', $variantsData[0]['productSku']);
            $this->assertFalse($variantsData[0]['deleted'], "simple_10 should not have been flag as deleted");
        } catch (Throwable $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * @param $childSku
     * @param $status
     * @return void
     */
    private function changeProductStatusProduct($childSku, $status)
    {
        try {
            $objectManager = Bootstrap::getObjectManager();
            /** @var ProductRepositoryInterface $productRepository */
            $productRepository = $objectManager->create(ProductRepositoryInterface::class);
            $childProduct = $productRepository->get($childSku);
            $productAction = Bootstrap::getObjectManager()->get(Action::class);
            $productAction->updateAttributes(
                [$childProduct->getEntityId()],
                [ProductAttributeInterface::CODE_STATUS => $status],
                $childProduct->getStoreId()
            );
        } catch (Throwable $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * Returns variants by IDs
     *
     * @param array $ids
     * @param bool $excludeDeleted
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    private function getVariantByIds(array $ids, bool $excludeDeleted = false): array
    {
        $output = [];
        foreach ($this->productVariantsFeed->getFeedSince('1')['feed'] as $item) {
            if (in_array($item['id'], $ids) && (!$excludeDeleted || !$item['deleted'])) {
                $output[] = $item;
            }
        }
        return $output;
    }

    /**
     * Delete product variant
     *
     * @param string $productSku
     * @throws RuntimeException
     */
    private function deleteProduct(string $productSku): void
    {
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', true);

        try {
            $this->productRepository->deleteById($productSku);
        } catch (Throwable $e) {
            throw new RuntimeException('Could not delete product ' . $productSku);
        }

        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', false);
    }

    /**
     * Get the expected variants for the first combination of options being tested.
     *
     * @param array $simples
     * @return array
     */
    private function getExpectedProductVariants(array $simples): array
    {

        $variants = [
            'simple_10' => [
                'id' => '8a880c29baa2ec8a5068350ec04f5b7d',
                'optionValues' => [
                    [
                        'attributeCode' => 'test_configurable_first',
                        'label' => 'First Option 1',
//                                'valueIndex' => '107', //Skipped because they are unique
//                                'uid' => 'Y29uZmlndXJhYmxlLzIwOS8xMDc=', //Skipped because they are unique
                    ],
                    [
                        'attributeCode' => 'test_configurable_second',
                        'label' => 'Second Option 1',
                    ],
                ],
                'parentId' => '1',
                'productId' => '10',
                'parentSku' => 'configurable',
                'productSku' => 'simple_10',
                'deleted' => false,
            ],
            'simple_20' => [
                'id' => 'b91c35230afd24649f2ff60c79e7e7ba',
                'optionValues' => [
                    [
                        'attributeCode' => 'test_configurable_first',
                        'label' => 'First Option 2',
                    ],
                    [
                        'attributeCode' => 'test_configurable_second',
                        'label' => 'Second Option 2',
                    ],
                ],
                'parentId' => '1',
                'productId' => '20',
                'parentSku' => 'configurable',
                'productSku' => 'simple_20',
                'deleted' => false,
            ],
        ];
        return array_values(array_intersect_key($variants, array_flip($simples)));
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->truncateIndexTable();
    }

    /**
     * Truncates index table
     */
    private function truncateIndexTable(): void
    {
        $connection = $this->resource->getConnection();
        $feedTable = $this->resource->getTableName($this->productVariantsFeed->getFeedMetadata()->getFeedTableName());
        $connection->truncateTable($feedTable);
    }
}
