<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Sales/_files/default_rollback.php');
Resolver::getInstance()->requireDataFixture('Magento/ConfigurableProduct/_files/product_configurable.php');
/** @var \Magento\Catalog\Model\Product $configurableProduct */

$addressData = include INTEGRATION_TESTS_DIR . '/testsuite/Magento/Sales/_files/address_data.php';

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);
$configurableProduct = $productRepository->get('configurable');

/** @var \Magento\Sales\Model\Order\Address $billingAddress */
$billingAddress = $objectManager->create(\Magento\Sales\Model\Order\Address::class, ['data' => $addressData]);
$billingAddress->setAddressType('billing');

$shippingAddress = clone $billingAddress;
$shippingAddress->setId(null)->setAddressType('shipping');

/** @var Payment $payment */
$payment = $objectManager->create(Payment::class);
$payment->setMethod('checkmo')
    ->setAdditionalInformation('last_trans_id', '11122')
    ->setAdditionalInformation(
        'metadata',
        [
            'type' => 'free',
            'fraudulent' => false,
        ]
    );

$qtyOrdered = 2;
$productOptions = ['color' => 'red', 'size' => 'medium'];
/** @var \Magento\Sales\Model\Order\Item $orderItem */
$orderConfigurableItem = $objectManager->create(\Magento\Sales\Model\Order\Item::class);
$orderConfigurableItem->setProductId($configurableProduct->getId())->setQtyOrdered($qtyOrdered);
$orderConfigurableItem->setBasePrice($configurableProduct->getPrice());
$orderConfigurableItem->setPrice($configurableProduct->getPrice());
$orderConfigurableItem->setRowTotal($configurableProduct->getPrice());
$orderConfigurableItem->setParentItemId(null);
$orderConfigurableItem->setProductType('configurable');
$orderConfigurableItem->setProductOptions($productOptions);

if ($configurableProduct->getExtensionAttributes()
    && (array)$configurableProduct->getExtensionAttributes()->getConfigurableProductLinks()
) {
    $configurableProductId = current($configurableProduct->getExtensionAttributes()->getConfigurableProductLinks());
    /** @var \Magento\Catalog\Api\Data\ProductInterface $simpleProduct */
    $simpleProduct = $productRepository->getById($configurableProductId);
    $orderItem = $objectManager->create(\Magento\Sales\Api\Data\OrderItemInterface::class);
    $orderItem->setBasePrice($simpleProduct->getPrice());
    $orderItem->setPrice($simpleProduct->getPrice());
    $orderItem->setRowTotal($simpleProduct->getPrice());
    $orderItem->setProductType('simple');
    $orderItem->setProductOptions($productOptions);
    // duplicate behavior with simple product associated with configurable one that happens during order process.
    $orderItem->setProductId($simpleProduct->getId())->setQtyOrdered($qtyOrdered);
    $orderItem->setParentItemId($orderConfigurableItem->getId());
}

/** @var Order $order */
$order = $objectManager->create(Order::class);
$order->setIncrementId('100000001')
    ->setState(Order::STATE_PROCESSING)
    ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING))
    ->setSubtotal(100)
    ->setGrandTotal(100)
    ->setBaseSubtotal(100)
    ->setBaseGrandTotal(100)
    ->setOrderCurrencyCode('USD')
    ->setBaseCurrencyCode('USD')
    ->setCustomerIsGuest(true)
    ->setCustomerEmail('customer@null.com')
    ->setBillingAddress($billingAddress)
    ->setShippingAddress($shippingAddress)
    ->setStoreId($objectManager->get(StoreManagerInterface::class)->getStore()->getId())
    ->addItem($orderConfigurableItem)
    ->addItem($orderItem)
    ->setPayment($payment);

/** @var OrderRepositoryInterface $orderRepository */
$orderRepository = $objectManager->create(OrderRepositoryInterface::class);
$orderRepository->save($order);
