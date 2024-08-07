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

use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Sales/_files/order.php');

$objectManager = Bootstrap::getObjectManager();
/** @var \Magento\Sales\Model\Order $order */
$order = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(\Magento\Sales\Model\Order::class);
$order->loadByIncrementId('100000001');
$order->setData('base_to_global_rate', 2.1)
    ->setData('base_shipping_amount', 20.1)
    ->setData('base_shipping_canceled', 2.1)
    ->setData('base_shipping_invoiced', 20.1)
    ->setData('base_shipping_refunded', 3.1)
    ->setData('is_virtual', 0)
    ->setData('shipping_method', 'flatrate_flatrate')
    ->setData('shipping_description', 'flatrate description')
    ->setData('shipping_amount', 1.5)
    ->setBaseShippingTaxAmount(2)
    ->setBaseTaxAmount(3)
    ->setBaseDiscountAmount(5)
    ->setBaseTotalInvoiced(30)
    ->save();

$orderItems = $order->getItems();
/** @var \Magento\Sales\Api\Data\OrderItemInterface $orderItem */
$orderItem = array_values($orderItems)[0];

/** @var \Magento\Sales\Api\Data\InvoiceItemCreationInterface $invoiceItem */
$invoiceItem = $objectManager->create(\Magento\Sales\Api\Data\InvoiceItemCreationInterface::class);
$invoiceItem->setOrderItemId($orderItem->getItemId());
$invoiceItem->setQty($orderItem->getQtyOrdered());

/** @var \Magento\Sales\Api\InvoiceOrderInterface $invoiceOrder */
$invoiceOrder = $objectManager->create(\Magento\Sales\Api\InvoiceOrderInterface::class);
$invoiceOrder->execute($order->getEntityId(), false, [$invoiceItem]);

/** @var \Magento\Sales\Api\Data\ShipmentItemCreationInterface $shipmentItem */
$shipmentItem = $objectManager->create(\Magento\Sales\Api\Data\ShipmentItemCreationInterface::class);
$shipmentItem->setOrderItemId($orderItem->getItemId());
$shipmentItem->setQty($orderItem->getQtyOrdered());

/** @var \Magento\Sales\Api\ShipOrderInterface $shipOrder */
$shipOrder = $objectManager->create(\Magento\Sales\Api\ShipOrderInterface::class);
$shipOrder->execute($order->getEntityId(), [$shipmentItem]);
