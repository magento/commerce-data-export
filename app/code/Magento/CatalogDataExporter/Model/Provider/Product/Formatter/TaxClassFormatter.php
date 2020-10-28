<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
