<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\ProductAttributeMetadata;

use Magento\CatalogDataExporter\Model\Query\AttributeOptionsQuery;
use Magento\Framework\App\ResourceConnection;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * Attribute options data provider
 */
class AttributeOptions
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var AttributeOptionsQuery
     */
    private $attributeOptionsQuery;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param AttributeOptionsQuery $attributeOptionsQuery
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        AttributeOptionsQuery $attributeOptionsQuery,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->attributeOptionsQuery = $attributeOptionsQuery;
        $this->logger = $logger;
    }

    /**
     * Get provider data
     *
     * @param array $values
     * @return array
     * @throws UnableRetrieveData
     */
    public function get(array $values) : array
    {
        $connection = $this->resourceConnection->getConnection();
        $queryArguments = [];
        try {
            foreach ($values as $value) {
                $queryArguments[$value['storeViewCode']][$value['id']] = $value['id'];
            }

            $output = [];
            foreach ($queryArguments as $storeViewCode => $attributeIds) {
                $sql = $this->attributeOptionsQuery->getQuery($attributeIds, $storeViewCode);
                $results = $connection->fetchAll($sql);
                if (!empty($results)) {
                    foreach ($results as $result) {
                        if (!empty($result['attributeOptions'])) {
                            $output[] = $result;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Unable to retrieve attribute options data. Error: ' . $e->getMessage(), ['exception' => $e]);
            throw new UnableRetrieveData('Unable to retrieve attribute options data. Error: ' . $e->getMessage(), 0, $e);
        }
        return $output;
    }
}
