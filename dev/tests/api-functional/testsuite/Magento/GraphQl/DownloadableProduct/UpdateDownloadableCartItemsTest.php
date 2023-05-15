<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\DownloadableProduct;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\ObjectManager\ObjectManager;
use Magento\GraphQl\Quote\GetMaskedQuoteIdByReservedOrderId;
use Magento\GraphQl\Quote\GetQuoteItemIdByReservedQuoteIdAndSku;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test cases for adding downloadable product to cart.
 */
class UpdateDownloadableCartItemsTest extends GraphQlAbstract
{
    /**
     * @var GetMaskedQuoteIdByReservedOrderId
     */
    private $getMaskedQuoteIdByReservedOrderId;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var GetQuoteItemIdByReservedQuoteIdAndSku
     */
    private $getQuoteItemIdByReservedQuoteIdAndSku;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var QuoteResource
     */
    private $quoteResource;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->getMaskedQuoteIdByReservedOrderId = $this->objectManager->get(GetMaskedQuoteIdByReservedOrderId::class);
        $this->quoteFactory = $this->objectManager->get(QuoteFactory::class);
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->quoteResource = $this->objectManager->get(QuoteResource::class);
        $this->getQuoteItemIdByReservedQuoteIdAndSku = $this->objectManager->get(
            GetQuoteItemIdByReservedQuoteIdAndSku::class
        );
    }

    /**
     * Update a downloadable product into shopping cart when "Links can be purchased separately" is enabled
     *
     * @magentoApiDataFixture Magento/Checkout/_files/active_quote_with_downloadable_product.php
     */
    public function testUpdateDownloadableCartItemQuantity()
    {
        $this->markTestSkipped('Skip this test as it fails with SaaS-Export extension MDEE-187');
    }

    /**
     * Update a downloadable product into shopping cart when "Links can be purchased separately" is enabled
     *
     * @magentoApiDataFixture Magento/Checkout/_files/active_quote_with_downloadable_product.php
     */
    public function testRemoveCartItemIfQuantityIsZero()
    {
        $this->markTestSkipped('Skip this test as it fails with SaaS-Export extension MDEE-187');
    }
}
