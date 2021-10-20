<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryDataExporter\Model\Provider;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\QueryXml\Model\QueryProcessor;

/**
 * Class for getting infinite stock value for stock item.
 */
class InfiniteStock
{
    /**
     * @var QueryProcessor
     */
    private $queryProcessor;

    /**
     * @var string
     */
    private $queryName;

    /**
     * @var string[]
     */
    private $data;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @param QueryProcessor $queryProcessor
     * @param StockConfigurationInterface $stockConfiguration
     * @param string $queryName
     * @param string[] $data
     */
    public function __construct(
        QueryProcessor $queryProcessor,
        StockConfigurationInterface $stockConfiguration,
        string $queryName,
        array $data = []
    ) {
        $this->data = $data;
        $this->queryName = $queryName;
        $this->queryProcessor = $queryProcessor;
        $this->stockConfiguration = $stockConfiguration;
    }

    /**
     * Getting inventory stock statuses.
     *
     * @param array $values
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function get(array $values): array
    {
        $queryArguments = $this->data;
        $configManageStock = $this->stockConfiguration->getManageStock();
        $configBackorders = $this->stockConfiguration->getBackorders();
        foreach ($values as $value) {
            $queryArguments['skus'][] = $value['sku'];
        }
        $output = [];
        $cursor = $this->queryProcessor->execute($this->queryName, $queryArguments);
        while ($row = $cursor->fetch()) {
            $itemInfiniteStock['sku'] = $row['sku'];
            $itemInfiniteStock['infiniteStock'] = $this->getIsInfiniteStock(
                $row,
                (bool)$configManageStock,
                (bool)$configBackorders
            );
            $output[] = $itemInfiniteStock;
        }
        return $output;
    }

    /**
     * Check is item stock is infinite
     *
     * @param array $row
     * @param bool $configManageStock
     * @param bool $configBackorders
     * @return bool
     */
    private function getIsInfiniteStock(array $row, bool $configManageStock, bool $configBackorders): bool
    {
        $isInfinite = false === $configManageStock || true === $configBackorders;
        if (false === (bool)$row['useConfigManageStock'] && isset($row['manageStock'])) {
            $isInfinite = !(bool)$row['manageStock'];
        }
        if (false === $isInfinite && false === (bool)$row['useConfigBackorders'] && isset($row['backorders'])) {
            $isInfinite = (bool)$row['backorders'];
        }
        return $isInfinite;
    }
}
