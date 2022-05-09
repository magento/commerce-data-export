<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesDataExporter\Tests\Integration;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\DataExporter\Uuid\ResourceModel\UuidResource;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\ShipmentTrackInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Shipment;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Sales\Model\Order\Payment\Transaction\Repository as TransactionRepository;

/**
 * Tests for orders data exporter functionality
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
     * @param string $orderNumber
     * @param string[] $dataToVerify
     *
     * @dataProvider orderWithTwoItemsDataProvider
     * @magentoDataFixture Magento/Sales/_files/customer_order_with_two_items.php
     *
     * @return void
     * @throws \Zend_Db_Statement_Exception
     * @throws \Magento\Framework\Exception\InputException
     */
    public function testOrderWithTwoProductsInformation(string $orderNumber, array $dataToVerify): void
    {
        /** @var OrderInterface $order */
        $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);
        $orderId = $order->getEntityId();
        $this->runIndexer([$orderId]);
        $orderFeed = $this->getOrderFeedByIds([$orderId])[0];
        $expectedOrdersData = $this->getOrderDataToVerify($order, $dataToVerify);

        $this->checkFields($expectedOrdersData, $orderFeed);
    }

    /**
     * @param string $orderNumber
     * @param string[] $dataToVerify
     *
     * @dataProvider orderFullWorkflowDataProvider
     * @magentoDataFixture Magento/SalesDataExporter/_files/order_full_work_flow.php
     *
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function testOrderFullWorkflowInformation(string $orderNumber, array $dataToVerify): void
    {
        /** @var OrderInterface $order */
        $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);
        $orderId = $order->getEntityId();
        $this->runIndexer([$orderId]);
        $orderFeed = $this->getOrderFeedByIds([$orderId])[0];
        $expectedOrdersData = $this->getOrderDataToVerify($order, $dataToVerify);

        $this->checkFields($expectedOrdersData, $orderFeed);
    }

    /**
     * @param string $orderNumber
     * @param string[] $dataToVerify
     *
     * @dataProvider orderWithTaxableProductDataProvider
     * @magentoDataFixture Magento/Sales/_files/customer_order_with_taxable_product.php
     *
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function testOrderWithTaxableProductInformation(string $orderNumber, array $dataToVerify): void
    {
        /** @var OrderInterface $order */
        $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);
        $orderId = $order->getEntityId();
        $this->runIndexer([$orderId]);
        $orderFeed = $this->getOrderFeedByIds([$orderId])[0];
        $expectedOrdersData = $this->getOrderDataToVerify($order, $dataToVerify);

        $this->checkFields($expectedOrdersData, $orderFeed);
    }

    /**
     * @param string $orderNumber
     * @param string[] $dataToVerify
     *
     * @dataProvider orderWithInvoiceAndCustomStatusDataProvider
     * @magentoDataFixture Magento/Sales/_files/order_with_invoice_and_custom_status.php
     *
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function testOrderWithInvoiceAndCustomStatus(string $orderNumber, array $dataToVerify): void
    {
        /** @var OrderInterface $order */
        $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);
        $orderId = $order->getEntityId();
        $this->runIndexer([$orderId]);
        $orderFeed = $this->getOrderFeedByIds([$orderId])[0];
        $expectedOrdersData = $this->getOrderDataToVerify($order, $dataToVerify);

        $this->checkFields($expectedOrdersData, $orderFeed);
    }

    /**
     * @param string $orderNumber
     * @param string[] $dataToVerify
     *
     * @dataProvider orderWithCreditMemoDataProvider
     * @magentoDataFixture Magento/SalesDataExporter/_files/order_with_invoice_shipment_creditmemo.php
     *
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function testOrderWithCreditMemo(string $orderNumber, array $dataToVerify): void
    {
        /** @var OrderInterface $order */
        $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);
        $orderId = $order->getEntityId();
        $this->runIndexer([$orderId]);
        $orderFeed = $this->getOrderFeedByIds([$orderId])[0];
        $expectedOrdersData = $this->getOrderDataToVerify($order, $dataToVerify);

        $this->checkFields($expectedOrdersData, $orderFeed);
    }

    /**
     * @param string $orderNumber
     * @param string[] $dataToVerify
     *
     * @dataProvider orderWithConfigurableProductDataProvider
     * @magentoDataFixture Magento/SalesDataExporter/_files/order_configurable_product.php
     *
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function testOrderWithConfigurableProduct(string $orderNumber, array $dataToVerify): void
    {
        /** @var OrderInterface $order */
        $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);
        $orderId = $order->getEntityId();
        $this->runIndexer([$orderId]);
        $orderFeed = $this->getOrderFeedByIds([$orderId])[0];
        $expectedOrdersData = $this->getOrderDataToVerify($order, $dataToVerify);

        $this->checkFields($expectedOrdersData, $orderFeed);
    }

    /**
     * @param string $orderNumber
     * @param string[] $dataToVerify
     *
     * @dataProvider orderWithTransactionsDataProvider
     * @magentoDataFixture Magento/SalesDataExporter/_files/transactions_detailed.php
     *
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function testOrderWithTransactions(string $orderNumber, array $dataToVerify): void
    {
        /** @var OrderInterface $order */
        $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);
        $orderId = $order->getEntityId();
        $this->runIndexer([$orderId]);
        $orderFeed = $this->getOrderFeedByIds([$orderId])[0];
        $expectedOrdersData = $this->getOrderDataToVerify($order, $dataToVerify);

        $this->checkFields($expectedOrdersData, $orderFeed);
    }

    /**
     * @param string $orderNumber
     * @param string[] $dataToVerify
     *
     * @dataProvider orderWithAdditionalInformationDataProvider
     * @magentoDataFixture Magento/SalesDataExporter/_files/order_with_additional_information.php
     *
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function testOrderWithAdditionalData(string $orderNumber, array $dataToVerify): void
    {
        /** @var OrderInterface $order */
        $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);
        $orderId = $order->getEntityId();
        $this->runIndexer([$orderId]);
        $orderFeed = $this->getOrderFeedByIds([$orderId])[0];
        $expectedOrdersData = $this->getOrderDataToVerify($order, $dataToVerify);

        $this->checkFields($expectedOrdersData, $orderFeed);
    }

    /**
     * @return array[]
     */
    public function orderWithTwoItemsDataProvider(): array
    {
        return [
            [
                'order_number' => '100000555', //customer_order_with_two_items
                'entities_to_verify' => [
                    'order_data',
                    'items'
                ]
            ]
        ];
    }

    /**
     * @return array[]
     */
    public function orderWithTaxableProductDataProvider(): array
    {
        return [
            [
                'order_number' => 'test_order_with_taxable_product', //customer_order_with_taxable_product
                'entities_to_verify' => [
                    'order_data',
                    'items'
                ]
            ]
        ];
    }

    /**
     * @return array[]
     */
    public function orderFullWorkflowDataProvider(): array
    {
        return [
            [
                'order_number' => '100000001', //order_full_work_flow
                'entities_to_verify' => [
                    'order_data',
                    'items',
                    'shipments',
                    'invoice',
                    'creditmemo'
                ]
            ]
        ];
    }

    /**
     * @return array[]
     */
    public function orderWithInvoiceAndCustomStatusDataProvider(): array
    {
        return [
            [
                'order_number' => '100000001', //order_with_invoice_and_custom_status
                'entities_to_verify' => [
                    'order_data',
                    'items',
                    'invoice'
                ]
            ],
        ];
    }

    /**
     * @return array[]
     */
    public function orderWithCreditMemoDataProvider(): array
    {
        return [
            [
                'order_number' => '100000111', //order_with_invoice_shipment_creditmemo
                'entities_to_verify' => [
                    'order_data',
                    'items',
                    'credit_memo'
                ]
            ],
        ];
    }

    /**
     * @return array[]
     */
    public function orderWithConfigurableProductDataProvider(): array
    {
        return [
            [
                'order_number' => '100000001', //order_configurable_product
                'entities_to_verify' => [
                    'order_data',
                    'items'
                ]
            ],
        ];
    }

    /**
     * @return array[]
     */
    public function orderWithTransactionsDataProvider(): array
    {
        return [
            [
                'order_number' => '100000001', //transactions_detailed
                'entities_to_verify' => [
                    'order_data',
                    'transactions'
                ]
            ],
        ];
    }

    /**
     * @return array[]
     */
    public function orderWithAdditionalInformationDataProvider(): array
    {
        return [
            [
                'order_number' => '100000001', //additional_information
                'entities_to_verify' => [
                    'order_data',
                    'items',
                    'additional_information'
                ]
            ],
        ];
    }

    /**
     * @param array $expectedOrderData
     * @param array $feedData
     */
    private function checkFields(array $expectedOrderData, array $feedData): void
    {
        foreach ($expectedOrderData as $expectedField => $expectedData) {
            if ($expectedField === 'items') {
                $uncheckedIds = \array_flip(array_keys($expectedData));
                foreach ($feedData['items'] as $itemData) {
                    $itemIdField = isset($itemData['itemId']) ? 'itemId' : 'orderItemId';
                    $expectedDataId = $itemData[$itemIdField]['id'];
                    if (isset($itemData[$itemIdField])) {
                        $this->checkFields($expectedData[$expectedDataId], $itemData);
                        unset($uncheckedIds[$expectedDataId]);
                    }
                }
                self::assertEmpty($uncheckedIds, "Some items are missed in feed");
                continue;
            }
            if ($expectedField === 'shipments') {
                $uncheckedIds = \array_flip(array_keys($expectedData));
                foreach ($feedData['shipments'] as $itemData) {
                    $this->checkFields($expectedData[$itemData['shipmentId']['id']], $itemData);
                    unset($uncheckedIds[$itemData['shipmentId']['id']]);
                }
                self::assertEmpty($uncheckedIds, "Some shipment items are missed in feed");
                continue;
            }
            if ($expectedField === 'invoices') {
                $uncheckedIds = \array_flip(array_keys($expectedData));
                foreach ($feedData[$expectedField] as $itemData) {
                    $this->checkFields($expectedData[$itemData['entityId']], $itemData);
                    unset($uncheckedIds[$itemData['entityId']]);
                }
                self::assertEmpty($uncheckedIds, "Some invoice items are missed in feed");
                continue;
            }

            if ($expectedField === 'creditMemos') {
                $uncheckedIds = \array_flip(array_keys($expectedData));
                foreach ($feedData[$expectedField] as $itemData) {
                    $this->checkFields($expectedData[$itemData['creditMemoId']['id']], $itemData);
                    unset($uncheckedIds[$itemData['creditMemoId']['id']]);
                }
                self::assertEmpty($uncheckedIds, "Some credit memo items are missed in feed");
                continue;
            }

            if ($expectedField === 'transactions') {
                $uncheckedIds = \array_flip(array_keys($expectedData));
                foreach ($feedData[$expectedField] as $itemData) {
                    $this->checkFields($expectedData[$itemData['entityId']], $itemData);
                    unset($uncheckedIds[$itemData['entityId']]);
                }
                self::assertEmpty($uncheckedIds, "Some transaction items are missed in feed");
                continue;
            }

            if (isset($feedData[$expectedField])) {
                if (!\is_array($feedData[$expectedField])) {
                    self::assertEquals(
                        $expectedData,
                        $feedData[$expectedField],
                        sprintf(
                            "Expected data: %s doesn't equal to real field %s value: %s",
                            $expectedData,
                            $expectedField,
                            $feedData[$expectedField]
                        )
                    );
                } else {
                    $this->checkFields($expectedOrderData[$expectedField], $feedData[$expectedField]);
                }
            }
        }
    }

    /**
     * @param OrderInterface $order
     * @param array $dataToVerify
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     */
    private function getOrderDataToVerify(OrderInterface $order, array $dataToVerify): array
    {
        $expectedOrderData = [];
        if (array_contains($dataToVerify, 'order_data')) {
            $expectedOrderData = $this->getExpectedOrderData($order);
        }
        if (array_contains($dataToVerify, 'transactions')) {
            $expectedOrderData['transactions'] = $this->getExpectedTransactionsData($order);
        }
        if (array_contains($dataToVerify, 'items')) {
            $expectedOrderData['items'] = $this->getExpectedOrderItemsData($order);
        }
        if (array_contains($dataToVerify, 'invoice')) {
            $expectedOrderData['invoices'] = $this->getExpectedInvoicesData($order);
        }
        if (array_contains($dataToVerify, 'credit_memo')) {
            $expectedOrderData['creditMemos'] = $this->getExpectedCreditMemosData($order);
        }
        if (array_contains($dataToVerify, 'shipments')) {
            $expectedOrderData['shipments'] = $this->getExpectedShipmentData($order);
        }

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
        $output = [];
        foreach ($this->ordersFeed->getFeedSince('1')['feed'] as $item) {
            if ((!$excludeDeleted || !$item['deleted']) && \in_array($item['commerceOrderId'], $ids)) {
                $output[] = $item;
            }
        }
        return $output;
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
            'entityId' => $order->getEntityId(),
            'commerceOrderNumber' => $order->getIncrementId(),
            'orderId' => ['id' => $this->uuidResource->getAssignedIds([$orderId], 'order')[$orderId]],
            'externalId' => ['id' => $orderId, 'salesChannel' => 'magento'],
            'createdAt' => $this->convertDate($order->getCreatedAt()),
            'updatedAt' => $this->convertDate($order->getUpdatedAt()),
            'state' => $this->mapOrderState($order->getState()),
            'status' => $order->getStatus(),
            'totalInvoiced' => $order->getBaseTotalInvoiced(),
            'grandTotal' => $order->getSubtotal(),
            'currency' => $order->getOrderCurrencyCode(),
            'amountPaid' => $order->getPayment()->getBaseAmountPaid(),
            'storeViewCode' => $order->getStore()->getCode(),
            'customerEmail' => $order->getCustomerEmail(),
            'additionalInformation' => $this->getExpectedOrderAdditionalInformationData($order),
            'payment' => [
                'billingAddress' => [
                    'phone' => $order->getBillingAddress()->getTelephone(),
                    'region' => $order->getBillingAddress()->getRegion(),
                    'postcode' => $order->getBillingAddress()->getPostcode(),
                    'street' => $order->getBillingAddress()->getStreet()[0],
                    'city' => $order->getBillingAddress()->getCity(),
                    'country' => $order->getBillingAddress()->getCountryId(),
                    'firstname' => $order->getBillingAddress()->getFirstname()
                ],
                'paymentMethodName' => $order->getPayment()->getAdditionalInformation()['method_title'] ?? '',
                'paymentMethodCode' => $order->getPayment()->getMethod() ?? '',
                'totalAmount' => $order->getBaseSubtotal(),
                'taxAmount' => $order->getBaseTaxAmount(),
                'currency' => $order->getOrderCurrencyCode()
            ],
            'shipping' => [
                'shippingAddress' => [
                    'phone' => $order->getShippingAddress()->getTelephone(),
                    'region' => $order->getShippingAddress()->getRegion(),
                    'postcode' => $order->getShippingAddress()->getPostcode(),
                    'street' => $order->getShippingAddress()->getStreet()[0],
                    'city' => $order->getShippingAddress()->getCity(),
                    'country' => $order->getShippingAddress()->getCountryId(),
                    'firstname' => $order->getShippingAddress()->getFirstname(),
                    'lastname' => $order->getShippingAddress()->getLastname()
                ],
                'shippingMethodName' => $order->getShippingDescription(),
                'shippingMethodCode' => $order->getShippingMethod(),
                'shippingAmount'      => $order->getShippingAmount(),
                'shippingTax'         => $order->getShippingTaxAmount(),
            ]
        ];
    }

    /**
     * @param OrderInterface $order
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     */
    private function getExpectedTransactionsData(OrderInterface $order): array
    {
        $transactions = [];
        foreach (self::TRANSACTION_TYPES as $transactionType) {
            $transaction = $this->transactionRepository->getByTransactionType(
                $transactionType,
                $order->getPayment()->getEntityId()
            );
            if ($transaction) {
                $transactions[$transaction->getId()] = [
                    'id' => $transaction->getId(),
                    'txnId' => $transaction->getTxnId(),
                    'type' => $transaction->getTxnType(),
                    'createdAt' => $this->convertDate($transaction->getCreatedAt())
                ];
            }
        }

        return $transactions;
    }

    /**
     * @param OrderInterface $order
     * @return array
     */
    private function getExpectedOrderItemsData(OrderInterface $order): array
    {
        $items = [];
        foreach ($order->getItems() as $orderItem) {
            $itemId = $orderItem->getItemId();
            $itemUuid = $this->uuidResource->getAssignedIds([$itemId], 'order_item')[$itemId];
            $items[$itemUuid] = [
                'itemId' => ['id' => $itemUuid],
                'entityId' => $itemId,
                'parentEntityId' => $orderItem->getParentItemId(),
                'isVirtual' => (bool)$orderItem->getIsVirtual(),
                'qtyInvoiced' => $orderItem->getQtyInvoiced(),
                'qtyShipped' => $orderItem->getQtyShipped(),
                'qtyBackordered' => $orderItem->getQtyBackordered(),
                'qtyOrdered' => $orderItem->getQtyOrdered(),
                'qtyRefunded' => $orderItem->getQtyRefunded(),
                'qtyCanceled' => $orderItem->getQtyCanceled(),
                'productType' => $orderItem->getProductType(),
                'itemsShippedTogether' => $orderItem->getProductType() === 'configurable',
                'sku' => $orderItem->getSku(),
                'name' => $orderItem->getName(),
                'qty' => $orderItem->getQtyOrdered(),
                'unitPrice' => $orderItem->getBasePrice(),
                'itemPrice' => $orderItem->getBaseRowTotal(),
                'discountAmount' => $orderItem->getDiscountAmount(),
                'taxAmount' => $orderItem->getTaxAmount(),
                'totalAmount' => $orderItem->getRowTotalInclTax(),
                'weight' => $orderItem->getWeight(),
                'additionalInformation' => $this->getExpectedItemAdditionalInformationData($orderItem)
            ];
        }
        return $items;
    }

    /**
     * @param OrderInterface $order
     * @return array
     */
    private function getExpectedInvoicesData(OrderInterface $order): array
    {
        $invoices = [];
        /** @var Invoice $invoiceItem */
        foreach ($order->getInvoiceCollection() as $invoiceItem) {
            $invoiceId = $invoiceItem->getId();
            $invoices[$invoiceId] = [
                'entityId' => $invoiceId,
                'isUsedForRefund' => false,
                'grandTotal' => $invoiceItem->getBaseGrandTotal()
            ];
        }

        return $invoices;
    }

    /**
     * @param OrderInterface $order
     * @return array
     */
    private function getExpectedCreditMemosData(OrderInterface $order): array
    {
        $creditMemos = [];
        /** @var Creditmemo $creditMemo */
        foreach ($order->getCreditmemosCollection() as $creditMemo) {
            $creditMemoItems = null;
            $creditMemoId = $creditMemo->getId();
            $creditMemoUuid = $this->uuidResource->getAssignedIds([$creditMemoId], 'credit_memo')[$creditMemoId];
            $creditMemos[$creditMemoUuid] = [
                'creditMemoId' => ['id' => $creditMemoUuid],
                'entityId' => $creditMemoId,
                'state' => $creditMemo->getState(),
                'createdAt' => $this->convertDate($creditMemo->getCreatedAt()),
                'shippingAmount' => $creditMemo->getBaseShippingAmount(),
                'shippingTaxAmount' => $creditMemo->getBaseShippingTaxAmount(),
                'adjustment' => $creditMemo->getAdjustment(),
                'currency' => $creditMemo->getOrderCurrencyCode(),
                //TODO: Need to be implemented
                //'refundTaxes' => $creditMemo->getTaxAmount()
                'subtotal' => $creditMemo->getBaseSubtotal(),
                'productsTaxAmount' => $creditMemo->getBaseTaxAmount()
            ];
            foreach ($creditMemo->getItems() as $creditmemoItem) {
                $creditMemoItemId = $creditmemoItem->getOrderItemId();
                $itemUuid = $this->uuidResource->getAssignedIds(
                    [$creditMemoItemId],
                    'order_item'
                )[$creditMemoItemId];
                $creditMemoItems[$itemUuid] = [
                    'orderItemId' => ['id' => $itemUuid],
                    'qtyRefunded' => $creditmemoItem->getQty(),
                    'basePrice' => $creditmemoItem->getBasePrice() . 1,
                    //TODO: Need to be implement
                    //'baseRowTotal' => $creditmemoItem->getBaseRowTotal(),
                    //TODO: Need to be implemented
                    //'productTaxes' => ''
                ];
            }
            $creditMemos[$creditMemoUuid]['refundItems'] = $creditMemoItems;
        }

        return $creditMemos;
    }

    /**
     * @param OrderInterface $order
     * @return array
     */
    private function getExpectedShipmentData(OrderInterface $order): array
    {
        $shipments = [];

        /** @var Shipment $orderShipment */
        foreach ($order->getShipmentsCollection() as $orderShipment) {
            $shipmentItems = null;
            $shipmentTrackItems = null;

            $shipmentId = $orderShipment->getId();
            $shipmentUuid = $this->uuidResource->getAssignedIds([$shipmentId], 'order_shipment')[$shipmentId];
            $expectedOrderData['shipments'][$shipmentUuid] = [
                'shipmentId' => ['id' => $shipmentUuid],
                'createdAt' => $this->convertDate($orderShipment->getCreatedAt()),
                'updatedAt' => $this->convertDate($orderShipment->getUpdatedAt())
            ];
            foreach ($orderShipment->getItems() as $shipmentItem) {
                $shippingOrderItemId = $shipmentItem->getOrderItemId();
                $itemUuid = $this->uuidResource->getAssignedIds(
                    [$shippingOrderItemId],
                    'order_item'
                )[$shippingOrderItemId];
                $shipmentItems[$itemUuid] = [
                    'orderItemId' => ['id' => $itemUuid],
                    'qtyShipped' => $shipmentItem->getQty()
                ];
            }
            /** @var ShipmentTrackInterface $shipmentTrack */
            foreach ($orderShipment->getTracks() as $shipmentTrack) {
                $shipmentTrackItems[$shipmentTrack->getEntityId()] = [
                    'qty' => $shipmentTrack->getQty()
                ];
            }

            $shipments[$shipmentUuid]['trackingInfo'] = $shipmentTrackItems;
            $shipments[$shipmentUuid]['items'] = $shipmentItems;
        }

        return $shipments;
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

        return $additionalInformation;
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
        return $additionalInformation;
    }
}
