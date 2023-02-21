<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\SalesOrdersDataExporter\Model;

use Generator;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\DataExporter\Uuid\UuidManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Query\BatchIteratorFactory;

class OrderUuidManager
{
    /**
     * @var array[]
     */
    private $mapTypes = [
        'order' => [
            'table' => 'sales_order',
            'id_column' => 'entity_id',
            'link_column' => 'entity_id'
        ],
        'order_item' => [
            'table' => 'sales_order_item',
            'id_column' => 'item_id',
            'link_column' => 'order_id'
        ],
        'credit_memo' => [
            'table' => 'sales_creditmemo',
            'id_column' => 'entity_id',
            'link_column' => 'order_id'
        ],
        'order_shipment' => [
            'table' => 'sales_shipment',
            'id_column' => 'entity_id',
            'link_column' => 'order_id'
        ]
    ];

    /**
     * @var UuidManager
     */
    private $uuidManager;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var BatchIteratorFactory
     */
    private $batchIteratorFactory;

    /**
     * @var CommerceDataExportLoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param UuidManager $uuidManager
     * @param BatchIteratorFactory $batchIteratorFactory
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        UuidManager $uuidManager,
        BatchIteratorFactory $batchIteratorFactory,
        CommerceDataExportLoggerInterface $logger
    ) {
        $this->uuidManager = $uuidManager;
        $this->resourceConnection = $resourceConnection;
        $this->batchIteratorFactory = $batchIteratorFactory;
        $this->logger = $logger;
    }

    /**
     * Assign uuids to order entities for the given date range
     *
     * @param int $batchSize
     * @param string|null $from
     * @param string|null $to
     * @param string $state
     * @return int
     */
    public function assignByDate(
        int $batchSize,
        string $from = null,
        string $to = null,
        string $state = ''
    ): int {
        $updatedEntities = 0;
        foreach ($this->getOrders($batchSize, $from, $to, $state) as $type => $entityIds) {
            $this->uuidManager->assignBulk($entityIds, $type);
            $updatedEntities += count($entityIds);
        }
        return $updatedEntities;
    }

    /**
     * Assign uuids to order entities for the given order ids
     *
     * @param int $batchSize
     * @param array $orderIds
     * @return int
     */
    public function assignByOrderIds(int $batchSize, array $orderIds): int
    {
        $updatedEntities = 0;
        foreach ($this->getOrders($batchSize, orderIds: $orderIds) as $type => $entityIds) {
            $this->uuidManager->assignBulk($entityIds, $type);
            $updatedEntities += count($entityIds);
        }
        return $updatedEntities;
    }

    /**
     * Retrieve affected entity types and its ids
     *
     * @param int $batchSize
     * @param string|null $from
     * @param string|null $to
     * @param string $state
     * @param array $orderIds
     * @return Generator
     */
    private function getOrders(
        int    $batchSize,
        string $from = null,
        string $to = null,
        string $state = '',
        array  $orderIds = []
    ): Generator {
        $mapTypes = array_map(function ($type) {
            $type['table'] = $this->resourceConnection->getTableName($type['table']);
            return $type;
        }, $this->mapTypes);

        $connection = $this->resourceConnection->getConnection();
        $uuidTableName = $this->resourceConnection->getTableName('data_exporter_uuid');
        $orderTableName = $mapTypes['order']['table'];

        foreach ($mapTypes as $type => $data) {
            $select = $connection->select()
                ->from(
                    ['order' => $orderTableName],
                    []
                )
                ->joinInner(
                    ['child' => $data['table']],
                    "order.entity_id = child.{$data['link_column']}",
                    "child.{$data['id_column']} AS entity_id"
                )
                ->joinLeft(
                    ['uuid' => $uuidTableName],
                    "child.{$data['id_column']} = uuid.entity_id and uuid.type = '{$type}'",
                    []
                )
                ->where('uuid.uuid IS NULL');

            if (!empty($state)) {
                $select->where('order.state = ?', $state);
            }
            if (!empty($from)) {
                $select->where('order.created_at >= ?', $from);
            }
            if (!empty($to)) {
                $select->where('order.created_at <= ?', $to);
            }
            if (!empty($orderIds)) {
                $select->where('order.entity_id IN (?)', $orderIds);
            }

            $iterator = $this->batchIteratorFactory->create(
                [
                    'select' => $select,
                    'batchSize' => $batchSize,
                    'correlationName' => 'child',
                    'rangeField' => $data['id_column'],
                    'rangeFieldAlias' => 'entity_id'
                ]
            );

            foreach ($iterator as $batchSelect) {
                yield $type => $connection->fetchCol($batchSelect);
            }
        }
    }
}
