<?php
/**
 * Copyright 2025 Adobe
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

namespace AdobeCommerce\ExtraProductAttributes\Provider;


use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\DB\Adapter\TableNotFoundException;
use AdobeCommerce\ExtraProductAttributes\Provider\Query\InventoryStockQuery;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * TODO: cover case for potentially differrent websites
 */
class AdvancedInventoryProvider
{
    private ?array $inventoryConfig = null;

    private const BACKORDERS_DEFAULT = 'no';
    private const BACKORDERS_MAP = [
        0 => self::BACKORDERS_DEFAULT,
        1 => 'allow',
        2 => 'allow_and_notify',
    ];

    /**
     * }
     * @param ResourceConnection $resourceConnection
     * @param InventoryStockQuery $query
     * @param DateTime $dateTime
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly InventoryStockQuery $query,
        // ignore deprecated StockConfigurationInterface
        private readonly StockConfigurationInterface $stockConfiguration,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Returned format:
     *  [
     *    <productId> => [
     *       <websiteCode> => [
     *          // inventory data
     *       ]
     *    ]
     *  ]
     */
    public function execute(array $productIds): array {
        $connection = $this->resourceConnection->getConnection();
        $output = [];

        try {
            $select = $this->query->getQuery($productIds);
            $cursor = $connection->query($select);
            while ($row = $cursor->fetch()) {
                $output[$row['productId']][$row['websiteCode']] = $this->formatData($row);
            }

        } catch (\Throwable $e) {
            $this->logger->error('StockStatus export error: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
        return $output;

    }

    /**
     * @param array $row
     * @return array
     */
    private function formatData(array $row): array
    {
        $data = [];
        $useConfigSuffix = '_config';
        $fields = [
            'manageStock',
            'cartMinQty',
            'cartMaxQty',
            'backorders',
            'enableQtyIncrements',
            'qtyIncrements',
        ];
        foreach ($fields as $field) {
            if (!isset($row[$field])) {
                $this->logger->warning(
                    sprintf(
                        'Field "%s" is missing in row %s',
                        $field,
                        var_export($row, true)
                    )
                );
                continue;
            }
            $useConfigField = $field . $useConfigSuffix;
            if (isset($row[$useConfigField]) && $row[$useConfigField]) {
                $configValue = $this->getInventoryConfig($field);
                $data[$field] = $configValue ?? $this->formatField($row[$field], $field);
            } else {
                $data[$field] = $this->formatField($row[$field], $field);
            }
        }

        return $data;
    }

    private function formatField(mixed $value, string $field): string|bool|null|float
    {
        return match ($field) {
            'enableQtyIncrements', 'manageStock' => (bool)$value,
            'cartMinQty', 'cartMaxQty', 'qtyIncrements' => (float)$value,
            'backorders' => self::BACKORDERS_MAP[$value] ?? self::BACKORDERS_DEFAULT,
            default => null,
        };
    }
    private function getInventoryConfig(string $field): mixed
    {
        if ($this->inventoryConfig == null) {
            $backorders = self::BACKORDERS_MAP[$this->stockConfiguration->getBackorders()] ?? self::BACKORDERS_DEFAULT;
            $this->inventoryConfig = [
                'manageStock' => (bool)$this->stockConfiguration->getManageStock(),
                'cartMinQty' => $this->stockConfiguration->getMinSaleQty(),
                'cartMaxQty' => $this->stockConfiguration->getMaxSaleQty(),
                'backorders' => $backorders,
                'enableQtyIncrements' => $this->stockConfiguration->getEnableQtyIncrements(),
                'qtyIncrements' => $this->stockConfiguration->getQtyIncrements(),
            ];
        }

        if (!isset($this->inventoryConfig[$field])) {
            $this->logger->warning(
                sprintf(
                    'Invalid field "%s" requested from inventory config %s',
                    $field,
                    var_export($this->inventoryConfig, true)
                )
            );
            return null;
        }
        return $this->inventoryConfig[$field];
    }
}
