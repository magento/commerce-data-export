<?php
/**
 * Copyright 2021 Adobe
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
