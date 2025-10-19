<?php
/**
 * Copyright 2022 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\ProductAttributeMetadata;

use Magento\CatalogDataExporter\Model\Query\AttributeOptionsQuery;
use Magento\Framework\App\ResourceConnection;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * Attribute options data provider
 * @deprecared 103.3.15 Will be removed in 104.* release
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
        } catch (\Exception $exception) {
            throw new UnableRetrieveData(
                sprintf('Unable to retrieve attribute options data: %s', $exception->getMessage()),
                0,
                $exception
            );
        }
        return $output;
    }
}
