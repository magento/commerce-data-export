<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesOrdersDataExporter\Test\Integration;

use Magento\DataExporter\Uuid\ResourceModel\UuidResource;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\ShipmentTrackInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment\Transaction\Repository as TransactionRepository;
use Magento\Sales\Model\Order\Shipment;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test for orders data exporter functionality
 *
 * @magentoDbIsolation enabled
 */
class CreateOrderTest extends AbstractOrderFeedTest
{
    private const TRANSACTION_TYPES = [
        'authorization',
        'capture'
    ];

    /**
     * @var OrderInterfaceFactory
     */
    private $orderFactory;

    /**
     * @var UuidResource
     */
    private $uuidResource;

    /**
     * @var TransactionRepository
     */
    private $transactionRepository;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $objectManager = Bootstrap::getObjectManager();
        $this->orderFactory = $objectManager->get(OrderInterfaceFactory::class);
        $this->uuidResource = $objectManager->get(UuidResource::class);
        $this->transactionRepository = $objectManager->get(TransactionRepository::class);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/customer_order_with_two_items.php
     *
     * @return void
     * @throws \Zend_Db_Statement_Exception
     * @throws InputException|NoSuchEntityException
     */
    public function testOrderWithTwoProductsInformation(): void
    {
        /** @var OrderInterface $order */
        $order = $this->orderFactory->create()->loadByIncrementId('100000555');
        $orderId = $order->getEntityId();
        $this->runIndexer([$orderId]);

        $orderFeed = $this->getOrderFeedByIds([$orderId])[0];
        $expectedOrderData = $this->getOrderDataToVerify($order);

        $this->checkFields($expectedOrderData, $orderFeed);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/customer_order_with_taxable_product.php
     *
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function testOrderWithTaxableProductInformation(): void
    {
        /** @var OrderInterface $order */
        $order = $this->orderFactory->create()->loadByIncrementId('test_order_with_taxable_product');
        $orderId = $order->getEntityId();
        $this->runIndexer([$orderId]);

        $orderFeed = $this->getOrderFeedByIds([$orderId])[0];
        $expectedOrderData = $this->getOrderDataToVerify($order);

        $this->checkFields($expectedOrderData, $orderFeed);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_with_invoice_and_custom_status.php
     *
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function testOrderWithInvoiceAndCustomStatus(): void
    {
        /** @var OrderInterface $order */
        $order = $this->orderFactory->create()->loadByIncrementId('100000001');
        $orderId = $order->getEntityId();
        $this->runIndexer([$orderId]);

        $orderFeed = $this->getOrderFeedByIds([$orderId])[0];
        $expectedOrderData = $this->getOrderDataToVerify($order);

        $this->checkFields($expectedOrderData, $orderFeed);
    }

    /**
     * @magentoDataFixture Magento_SalesOrdersDataExporter::Test/_files/order_full_work_flow.php
     *
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function testOrderFullWorkflowInformation(): void
    {
        /** @var OrderInterface $order */
        $order = $this->orderFactory->create()->loadByIncrementId('100000001');
        $orderId = $order->getEntityId();
        $this->runIndexer([$orderId]);

        $orderFeed = $this->getOrderFeedByIds([$orderId])[0];
        $expectedOrderData = $this->getOrderDataToVerify($order);

        $this->checkFields($expectedOrderData, $orderFeed);
    }

    /**
     * @magentoDataFixture Magento_SalesOrdersDataExporter::Test/_files/order_with_invoice_shipment_creditmemo.php
     *
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function testOrderWithCreditMemo(): void
    {
        /** @var OrderInterface $order */
        $order = $this->orderFactory->create()->loadByIncrementId('100000111');
        $orderId = $order->getEntityId();
        $this->runIndexer([$orderId]);

        $orderFeed = $this->getOrderFeedByIds([$orderId])[0];
        $expectedOrderData = $this->getOrderDataToVerify($order);

        $this->checkFields($expectedOrderData, $orderFeed);
    }

    /**
     * @magentoDataFixture Magento_SalesOrdersDataExporter::Test/_files/order_configurable_product.php
     *
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function testOrderWithConfigurableProduct(): void
    {
        /** @var OrderInterface $order */
        $order = $this->orderFactory->create()->loadByIncrementId('100000001');
        $orderId = $order->getEntityId();
        $this->runIndexer([$orderId]);

        $orderFeed = $this->getOrderFeedByIds([$orderId])[0];
        $expectedOrderData = $this->getOrderDataToVerify($order);

        $this->checkFields($expectedOrderData, $orderFeed);
    }

    /**
     * @magentoDataFixture Magento_SalesOrdersDataExporter::Test/_files/transactions_detailed.php
     *
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function testOrderWithTransactions(): void
    {
        /** @var OrderInterface $order */
        $order = $this->orderFactory->create()->loadByIncrementId('100000001');
        $orderId = $order->getEntityId();
        $this->runIndexer([$orderId]);

        $orderFeed = $this->getOrderFeedByIds([$orderId])[0];
        $expectedOrderData = $this->getOrderDataToVerify($order);

        $this->checkFields($expectedOrderData, $orderFeed);
    }

    /**
     * @magentoDataFixture Magento_SalesOrdersDataExporter::Test/_files/order_with_additional_information.php
     *
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function testOrderWithAdditionalData(): void
    {
        /** @var OrderInterface $order */
        $order = $this->orderFactory->create()->loadByIncrementId('100000001');
        $orderId = $order->getEntityId();
        $this->runIndexer([$orderId]);

        $orderFeed = $this->getOrderFeedByIds([$orderId])[0];
        $expectedOrderData = $this->getOrderDataToVerify($order);

        $this->checkFields($expectedOrderData, $orderFeed);
    }

    /**
     * @param array $expectedData
     * @param array $feedData
     */
    private function checkFields(array $expectedData, array $feedData): void
    {
        foreach ($expectedData as $field => $expectedValue) {
            if (is_array($expectedValue)) {
                $this->assertArrayHasKey($field, $feedData, sprintf('Field %s is not set in feed', $field));
                $this->checkFields($expectedValue, $feedData[$field]);
            } else {
                $this->assertFieldEquals($field, $expectedValue, $feedData);
            }
        }
    }

    /**
     * @param mixed $fieldName
     * @param mixed $expectedValue
     * @param array $actualData
     */
    private function assertFieldEquals(mixed $fieldName, mixed $expectedValue, array $actualData): void
    {
        if (array_key_exists($fieldName, $actualData)) {
            self::assertEquals(
                $expectedValue,
                $actualData[$fieldName],
                "Expected data: $expectedValue doesn't equal to real field $fieldName value: {$actualData[$fieldName]}"
            );
        } else {
            self::assertNull(
                $expectedValue,
                "Field $fieldName is not set in feed but the expected value was $expectedValue"
            );
        }
    }

    /**
     * @param OrderInterface $order
     * @return array
     * @throws InputException
     */
    private function getOrderDataToVerify(OrderInterface $order): array
    {
        $expectedOrderData = $this->getExpectedOrderData($order);
        $expectedOrderData['transactions'] = $this->getExpectedTransactionsData($order);
        $expectedOrderData['items'] = $this->getExpectedOrderItemsData($order);
        $expectedOrderData['invoices'] = $this->getExpectedInvoicesData($order);
        $expectedOrderData['creditMemos'] = $this->getExpectedCreditMemosData($order);
        $expectedOrderData['shipments'] = $this->getExpectedShipmentData($order);

        return $expectedOrderData;
    }

    /**
     * Returns orderFeeds by IDs
     *
     * @param array $ids
     * @param bool $excludeDeleted
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    private function getOrderFeedByIds(array $ids, bool $excludeDeleted = false): array
    {
        $filteredFeed = array_filter(
            $this->ordersFeed->getFeedSince('1')['feed'],
            function ($item) use ($ids, $excludeDeleted) {
                return (!$excludeDeleted || !$item['deleted']) && in_array($item['commerceOrderId'], $ids);
            }
        );
        return array_values($filteredFeed);
    }

    /**
     * Verify only order State available in Magento. Return null if state does not exist to generate error
     *
     * @param string $orderState
     * @return int|null
     */
    private function mapOrderState(string $orderState): ?int
    {
        static $map = [
            Order::STATE_NEW => 1,
            Order::STATE_PENDING_PAYMENT => 2,
            Order::STATE_PROCESSING => 3,
            Order::STATE_COMPLETE => 4,
            Order::STATE_CLOSED => 5,
            Order::STATE_CANCELED => 6,
            Order::STATE_HOLDED => 7,
            Order::STATE_PAYMENT_REVIEW => 8
        ];

        return $map[$orderState] ?? null;
    }

    /**
     * Convert date to RFC 3339
     */
    private function convertDate($date): string
    {
        return (new \DateTime($date))->format(\DateTimeInterface::RFC3339);
    }

    /**
     * @param OrderInterface $order
     * @return array
     */
    private function getExpectedOrderData(OrderInterface $order): array
    {
        $orderId = $order->getEntityId();
        return [
            'commerceOrderId' => $order->getEntityId(),
            'commerceOrderNumber' => $order->getIncrementId(),
            'orderId' => ['id' => $this->uuidResource->getAssignedIds([$orderId], 'order')[$orderId]],
            'externalId' => ['id' => $orderId, 'salesChannel' => 'magento'],
            'createdAt' => $this->convertDate($order->getCreatedAt()),
            'updatedAt' => $this->convertDate($order->getUpdatedAt()),
            'state' => $this->mapOrderState($order->getState()),
            'status' => $order->getStatus(),
            'totalInvoiced' => $order->getBaseTotalInvoiced(),
            'totalQtyOrdered' => $order->getTotalQtyOrdered(),
            'isVirtual' => $this->convertIntToBool($order->getIsVirtual()),
            'currency' => $order->getBaseCurrencyCode(),
            'subtotal' => $order->getBaseSubtotal(),
            'grandTotal' => $order->getBaseGrandTotal(),
            'discountAmount' => $order->getBaseDiscountAmount(),
            'amountCapturedOnline' => $order->getPayment()->getBaseAmountPaidOnline(),
            'amountRefundedOnline' => $order->getPayment()->getBaseAmountRefundedOnline(),
            'amountAuthorized' => $order->getPayment()->getBaseAmountAuthorized(),
            'amountPaid' => $order->getPayment()->getBaseAmountPaid(),
            'amountRefunded' => $order->getPayment()->getBaseAmountRefunded(),
            'amountCanceled' => $order->getPayment()->getBaseAmountCanceled(),
            'storeViewCode' => $order->getStore()->getCode(),
            'websiteCode' => $order->getStore()->getWebsite()->getCode(),
            'storeCode' => $order->getStore()->getWebsite()->getDefaultGroup()->getCode(),
            'customerEmail' => $order->getCustomerEmail(),
            'customerNote' => $order->getCustomerNote(),
            'additionalInformation' => $this->getExpectedOrderAdditionalInformationData($order),
            'payment' => [
                'billingAddress' => [
                    'phone' => $order->getBillingAddress()->getTelephone(),
                    'region' => $order->getBillingAddress()->getRegion(),
                    'postcode' => $order->getBillingAddress()->getPostcode(),
                    'street' => implode("\n", $order->getBillingAddress()->getStreet()),
                    'multiLineStreet' => $order->getBillingAddress()->getStreet(),
                    'city' => $order->getBillingAddress()->getCity(),
                    'country' => $order->getBillingAddress()->getCountryId(),
                    'firstname' => $order->getBillingAddress()->getFirstname()
                ],
                'paymentMethodName' => $order->getPayment()->getAdditionalInformation()['method_title'] ?? null,
                'paymentMethodCode' => $order->getPayment()->getMethod() ?? null,
                'totalAmount' => $order->getBaseSubtotal(),
                'taxAmount' => $order->getBaseTaxAmount(),
                'currency' => $order->getOrderCurrencyCode()
            ],
            'shipping' => [
                'shippingAddress' => [
                    'phone' => $order->getShippingAddress()->getTelephone(),
                    'region' => $order->getShippingAddress()->getRegion(),
                    'postcode' => $order->getShippingAddress()->getPostcode(),
                    'street' => implode("\n", $order->getShippingAddress()->getStreet()),
                    'multiLineStreet' => $order->getShippingAddress()->getStreet(),
                    'city' => $order->getShippingAddress()->getCity(),
                    'country' => $order->getShippingAddress()->getCountryId(),
                    'firstname' => $order->getShippingAddress()->getFirstname(),
                    'lastname' => $order->getShippingAddress()->getLastname()
                ],
                'shippingMethodName' => $order->getShippingDescription(),
                'shippingMethodCode' => $order->getShippingMethod(),
                'shippingAmount'     => $order->getBaseShippingAmount(),
                'shippingTax'        => $order->getBaseShippingTaxAmount(),
            ]
        ];
    }

    /**
     * @param OrderInterface $order
     * @return array|null
     * @throws InputException
     */
    private function getExpectedTransactionsData(OrderInterface $order): ?array
    {
        $transactions = [];
        foreach (self::TRANSACTION_TYPES as $transactionType) {
            $transaction = $this->transactionRepository->getByTransactionType(
                $transactionType,
                $order->getPayment()->getEntityId()
            );
            if ($transaction) {
                $transactions[] = [
                    'entityId' => $transaction->getId(),
                    'txnId' => $transaction->getTxnId(),
                    'type' => $transaction->getTxnType(),
                    'createdAt' => $this->convertDate($transaction->getCreatedAt())
                ];
            }
        }

        return empty($transactions) ? null : $transactions;
    }

    /**
     * @param OrderInterface $order
     * @return array|null
     */
    private function getExpectedOrderItemsData(OrderInterface $order): ?array
    {
        $items = [];
        foreach ($order->getItems() as $orderItem) {
            $itemId = $orderItem->getItemId();
            $itemUuid = $this->uuidResource->getAssignedIds([$itemId], 'order_item')[$itemId];
            $items[] = [
                'itemId' => ['id' => $itemUuid],
                'entityId' => $itemId,
                'parentEntityId' => $orderItem->getParentItemId(),
                'isVirtual' => $this->convertIntToBool($orderItem->getIsVirtual()),
                'qtyInvoiced' => $orderItem->getQtyInvoiced(),
                'qtyShipped' => $orderItem->getQtyShipped(),
                'qtyBackordered' => $orderItem->getQtyBackordered(),
                'qtyOrdered' => $orderItem->getQtyOrdered(),
                'qtyRefunded' => $orderItem->getQtyRefunded(),
                'qtyCanceled' => $orderItem->getQtyCanceled(),
                'productType' => $orderItem->getProductType(),
                'itemsShippedTogether' => $orderItem->getProductType() === 'configurable',
                'sku' => $orderItem->getSku(),
                'productSku' => $this->getExpectedOrderItemProductSku($orderItem),
                'name' => $orderItem->getName(),
                'qty' => $orderItem->getQtyOrdered(),
                'unitPrice' => $orderItem->getBasePrice(),
                'itemPrice' => $orderItem->getBaseRowTotal(),
                'discountAmount' => $orderItem->getBaseDiscountAmount(),
                'taxAmount' => $orderItem->getBaseTaxAmount(),
                'totalAmount' => $orderItem->getBaseRowTotalInclTax(),
                'weight' => $orderItem->getWeight(),
                'additionalInformation' => $this->getExpectedItemAdditionalInformationData($orderItem)
            ];
        }
        return empty($items) ? null : $items;
    }

    /**
     * Get the product SKU based on the product type.
     *
     * @param OrderItemInterface $orderItem
     * @return string|null
     */
    private function getExpectedOrderItemProductSku(OrderItemInterface $orderItem): ?string
    {
        if (in_array($orderItem->getProductType(), ['configurable', 'bundle'], true)) {
            return $orderItem->getProduct()->getSku();
        }

        return $orderItem->getSku();
    }

    /**
     * @param OrderInterface $order
     * @return array|null
     */
    private function getExpectedInvoicesData(OrderInterface $order): ?array
    {
        $invoices = [];
        /** @var Invoice $invoice */
        foreach ($order->getInvoiceCollection() as $invoice) {
            $invoiceId = $invoice->getId();
            $invoiceItems = [];
            /** @var Invoice\Item $invoiceItem */
            foreach ($invoice->getItems() as $invoiceItem) {
                $orderItemId = $invoiceItem->getOrderItemId();
                $itemUuid = $this->uuidResource->getAssignedIds([$orderItemId], 'order_item')[$orderItemId];
                $invoiceItems[] = [
                    'orderItemId' => ['id' => $itemUuid],
                    'qtyInvoiced' => $invoiceItem->getQty()
                ];
            }
            $invoices[] = [
                'entityId' => $invoiceId,
                'isUsedForRefund' => $this->convertIntToBool($invoice->getIsUsedForRefund()),
                'grandTotal' => $invoice->getBaseGrandTotal(),
                'createdAt' => $this->convertDate($invoice->getCreatedAt()),
                'commerceInvoiceNumber' => $invoice->getIncrementId(),
                'invoiceItems' => $invoiceItems
            ];
        }

        return empty($invoices) ? null : $invoices;
    }

    /**
     * @param OrderInterface $order
     * @return array|null
     */
    private function getExpectedCreditMemosData(OrderInterface $order): ?array
    {
        $creditMemos = [];
        /** @var Creditmemo $creditMemo */
        foreach ($order->getCreditmemosCollection() as $creditMemo) {
            $creditMemoItems = null;
            $creditMemoId = $creditMemo->getId();
            $creditMemoUuid = $this->uuidResource->getAssignedIds([$creditMemoId], 'credit_memo')[$creditMemoId];

            foreach ($creditMemo->getItems() as $creditmemoItem) {
                $creditMemoItemId = $creditmemoItem->getOrderItemId();
                $itemUuid = $this->uuidResource->getAssignedIds(
                    [$creditMemoItemId],
                    'order_item'
                )[$creditMemoItemId];
                $creditMemoItems[] = [
                    'orderItemId' => ['id' => $itemUuid],
                    'qtyRefunded' => $creditmemoItem->getQty(),
                    'basePrice' => $creditmemoItem->getBasePrice(),
                    'baseRowTotal' => $creditmemoItem->getQty() * $creditmemoItem->getBasePrice()
                        - $creditmemoItem->getBaseDiscountAmount(),
                    //TODO: Need to be implemented
                    //'productTaxes' => ''
                ];
            }

            $creditMemos[] = [
                'creditMemoId' => ['id' => $creditMemoUuid],
                'entityId' => $creditMemoId,
                'state' => $creditMemo->getState(),
                'createdAt' => $this->convertDate($creditMemo->getCreatedAt()),
                'shippingAmount' => $creditMemo->getBaseShippingAmount(),
                'shippingTaxAmount' => $creditMemo->getBaseShippingTaxAmount(),
                'adjustment' => $creditMemo->getBaseAdjustment(),
                'currency' => $creditMemo->getOrderCurrencyCode(),
                //TODO: Need to be implemented
                //'refundTaxes' => $creditMemo->getTaxAmount()
                'subtotal' => $creditMemo->getBaseSubtotal(),
                'productsTaxAmount' => $creditMemo->getBaseTaxAmount(),
                'commerceCreditMemoNumber' => $creditMemo->getIncrementId(),
                'grandTotal' => $creditMemo->getBaseGrandTotal(),
                'refundItems' => $creditMemoItems,
                'creditMemoComments' => $this->extractComments($creditMemo->getComments())
            ];
        }

        return empty($creditMemos) ? null : $creditMemos;
    }

    private function extractComments(array $comments): ?array
    {
        $commentValues = array_map(
            function ($comment) {
                return $comment->getComment();
            },
            $comments
        );
        return $commentValues ? array_values($commentValues) : null;
    }

    /**
     * @param OrderInterface $order
     * @return array|null
     */
    private function getExpectedShipmentData(OrderInterface $order): ?array
    {
        $shipments = [];

        /** @var Shipment $orderShipment */
        foreach ($order->getShipmentsCollection() as $orderShipment) {
            $shipmentItems = null;
            $shipmentTrackItems = null;

            $shipmentId = $orderShipment->getId();
            $shipmentUuid = $this->uuidResource->getAssignedIds([$shipmentId], 'order_shipment')[$shipmentId];

            foreach ($orderShipment->getItems() as $shipmentItem) {
                $itemId = $shipmentItem->getOrderItemId();
                $itemUuid = $this->uuidResource->getAssignedIds([$itemId], 'order_item')[$itemId];
                $shipmentItems[] = [
                    'orderItemId' => ['id' => $itemUuid],
                    'qtyShipped' => $shipmentItem->getQty()
                ];
            }

            /** @var ShipmentTrackInterface $shipmentTrack */
            foreach ($orderShipment->getTracks() as $shipmentTrack) {
                $shipmentTrackItems[] = [
                    'qty' => $shipmentTrack->getQty()
                ];
            }

            $shipments[] = [
                'shipmentId' => ['id' => $shipmentUuid],
                'createdAt' => $this->convertDate($orderShipment->getCreatedAt()),
                'updatedAt' => $this->convertDate($orderShipment->getUpdatedAt()),
                'commerceShipmentNumber' => $orderShipment->getIncrementId(),
                'trackingInfo' => $shipmentTrackItems,
                'items' => $shipmentItems
            ];
        }

        return empty($shipments) ? null : $shipments;
    }

    /**
     * @param OrderItemInterface $orderItem
     * @return array|null
     */
    private function getExpectedItemAdditionalInformationData(OrderItemInterface $orderItem): ?array
    {
        $additionalInformation = [];
        if (!empty($orderItem->getAdditionalData())) {
            foreach ($this->jsonSerializer->unserialize($orderItem->getAdditionalData()) as $name => $value) {
                $additionalInformation[] = [
                    'name' => $name,
                    'value' => \is_string($value) ? $value : $this->jsonSerializer->serialize($value)
                ];
            }
        }

        return empty($additionalInformation) ? null : $additionalInformation;
    }

    /**
     * @param OrderInterface $order
     * @return array|null
     */
    private function getExpectedOrderAdditionalInformationData(OrderInterface $order): ?array
    {
        $additionalInformation = [];
        if (!empty($order->getPayment()->getAdditionalInformation())) {
            foreach ($order->getPayment()->getAdditionalInformation() as $key => $value) {
                $additionalInformation[] = [
                    'name' => $key,
                    'value' => \is_string($value) ? $value : $this->jsonSerializer->serialize($value)
                ];
            }
        }
        return empty($additionalInformation) ? null : $additionalInformation;
    }

    private function convertIntToBool($value): ?bool
    {
        return $value !== null ? (bool) $value : null;
    }
}
