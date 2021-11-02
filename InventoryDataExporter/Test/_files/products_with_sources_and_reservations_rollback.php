<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 *
 * Delete products, sources and stocks from DB
 */
declare(strict_types=1);

use Magento\InventoryReservationsApi\Model\CleanupReservationsInterface;
use Magento\InventoryReservationsApi\Model\ReservationInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

/** @var CleanupReservationsInterface $cleanupReservations */
$cleanupReservations = Bootstrap::getObjectManager()->get(CleanupReservationsInterface::class);
$cleanupReservations->execute();

/** @var \Magento\Framework\App\ResourceConnection $resourceConnection */
$resourceConnection = Bootstrap::getObjectManager()->create(\Magento\Framework\App\ResourceConnection::class);
$connection = $resourceConnection->getConnection();
$reservationTable = $connection->getTableName('inventory_reservation');

$select = $connection->select()
    ->from(
        $reservationTable,
        ['GROUP_CONCAT(' . ReservationInterface::RESERVATION_ID . ')']
    );
$reservationIds = implode(',', $connection->fetchCol($select));

$condition = [ReservationInterface::RESERVATION_ID . ' IN (?)' => explode(',', $reservationIds)];
$connection->delete($reservationTable, $condition);

Resolver::getInstance()->requireDataFixture(
    'Magento_InventoryDataExporter::Test/_files/products_with_sources_rollback.php'
);
