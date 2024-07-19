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
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product\Formatter;

use Magento\Framework\App\ResourceConnection;

/**
 * Class TaxClassFormatter
 */
class TaxClassFormatter implements FormatterInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var array
     */
    private $dictionary;

    /**
     * TaxClassFormatter constructor.
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Load dictionary from resource table
     *
     * @return array
     */
    public function loadDictionary(): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->from(
            ['t' => $this->resourceConnection->getTableName('tax_class')],
            [
                'class_id',
                'class_name'
            ]
        );
        return $connection->fetchAssoc($select);
    }

    /**
     * Format data
     *
     * @param array $row
     * @return array
     */
    public function format(array $row): array
    {
        if (empty($this->dictionary)) {
            $this->dictionary = $this->loadDictionary();
        }
        if (isset($row['taxClassId']) && isset($this->dictionary[$row['taxClassId']])) {
            $row['taxClassId'] = $this->dictionary[$row['taxClassId']]['class_name'];
        }
        return $row;
    }
}
