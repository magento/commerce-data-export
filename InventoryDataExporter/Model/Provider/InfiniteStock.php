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
        foreach ($values as $value) {
            $queryArguments['skus'][] = $value['sku'];
        }
        $output = [];
        $cursor = $this->queryProcessor->execute($this->queryName, $queryArguments);
        while ($row = $cursor->fetch()) {
            $itemInfiniteStock = [
                'sku' => $row['sku'],
                'infiniteStock' => !$configManageStock
            ];
            if ((bool)$row['useConfigManageStock'] === false && isset($row['manageStock'])) {
                $itemInfiniteStock['infiniteStock'] = !(bool)$row['manageStock'];
            }
            $output[] = $itemInfiniteStock;
        }
        return $output;
    }
}
