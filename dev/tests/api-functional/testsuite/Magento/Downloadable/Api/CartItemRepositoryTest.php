<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Downloadable\Api;

use Magento\TestFramework\TestCase\WebapiAbstract;

class CartItemRepositoryTest extends WebapiAbstract
{
    const SERVICE_VERSION = 'V1';
    const SERVICE_NAME = 'quoteCartItemRepositoryV1';

    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    protected $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
    }

    /**
     * @magentoApiDataFixture Magento/Quote/_files/empty_quote.php
     * @magentoApiDataFixture Magento/Downloadable/_files/product_downloadable.php
     */
    public function testAddItem()
    {
        $this->markTestSkipped('Skip this test as it fails with SaaS-Export extension MDEE-389');
    }

    /**
     * @magentoApiDataFixture Magento/Quote/_files/empty_quote.php
     * @magentoApiDataFixture Magento/Downloadable/_files/product_downloadable.php
     */
    public function testAddItemWithInvalidLinkId()
    {
        $this->markTestSkipped('Skip this test as it fails with SaaS-Export extension MDEE-389');
    }

    /**
     * @magentoApiDataFixture Magento/Downloadable/_files/quote_with_downloadable_product.php
     */
    public function testUpdateItem()
    {
        $this->markTestSkipped('Skip this test as it fails with SaaS-Export extension MDEE-389');
    }

    /**
     * @magentoApiDataFixture Magento/Downloadable/_files/quote_with_downloadable_product.php
     */
    public function testUpdateItemWithInvalidLinkId()
    {
        $this->markTestSkipped('Skip this test as it fails with SaaS-Export extension MDEE-389');
    }

    /**
     * @magentoApiDataFixture Magento/Downloadable/_files/quote_with_downloadable_product.php
     */
    public function testGetList()
    {
        $this->markTestSkipped('Skip this test as it fails with SaaS-Export extension MDEE-389');
    }

    /**
     * @magentoApiDataFixture Magento/Downloadable/_files/quote_with_downloadable_product.php
     */
    public function testUpdateItemQty()
    {
        $this->markTestSkipped('Skip this test as it fails with SaaS-Export extension MDEE-389');
    }
}
