<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryDataExporter\Model\Provider;

use Magento\QueryXml\Model\QueryProcessor;

/**
 * Class for getting inventory stock statuses.
 */
class StockStatus
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
     * @param QueryProcessor $queryProcessor
     * @param string $queryName
     * @param string[] $data
     */
    public function __construct(
        QueryProcessor $queryProcessor,
        string $queryName,
        array $data = []
    ) {
        $this->data = $data;
        $this->queryName = $queryName;
        $this->queryProcessor = $queryProcessor;
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
        foreach ($values as $value) {
            $queryArguments['sourceItemIds'][] = $value['id'];
        }
        $output = [];
        $cursor = $this->queryProcessor->execute($this->queryName, $queryArguments);
        while ($row = $cursor->fetch()) {
            //TODO: extend query and remove this hardcode
//            $row['sku'] = "TestSku";
//            $row['qty'] = "10";
//            $row['qtyForSale'] = "4.5";
//            $row['infiniteStock'] = false;
//            $row['lowStock'] = true;
//            $row['updatedAt'] = "2021-07-22 17:38:36";
            $output[] = $row;
        }
        return $output;
    }
}