<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product;

use Magento\CatalogDataExporter\Model\Query\ProductAttributeQuery;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * Product attributes data provider
 */
class Attributes
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ProductAttributeQuery
     */
    private $attributeQuery;

    /**
     * @var AttributeMetadata
     */
    private $attributeMetadata;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ProductAttributeQuery $attributeQuery
     * @param AttributeMetadata $attributeMetadata
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ProductAttributeQuery $attributeQuery,
        AttributeMetadata $attributeMetadata,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->attributeQuery = $attributeQuery;
        $this->attributeMetadata = $attributeMetadata;
        $this->logger = $logger;
    }

    /**
     * Get provider data
     *
     * @param array $values
     * @return array
     * @throws UnableRetrieveData
     * @throws \Zend_Db_Statement_Exception
     */
    public function get(array $values): array
    {
        $output = [];
        $connection = $this->resourceConnection->getConnection();
        $queryArguments = [];
        foreach ($values as $value) {
            $queryArguments['productId'][$value['productId']] = $value['productId'];
            $queryArguments['storeViewCode'][$value['storeViewCode']] = $value['storeViewCode'];
        }
        try {
            foreach ($queryArguments['storeViewCode'] as $storeViewCode) {
                $select = $this->attributeQuery->getQuery(
                    [
                        'productId' => $queryArguments['productId'],
                        'storeViewCode' => $storeViewCode
                    ]
                );
                if ($select === null) {
                    continue;
                }

                $cursor = $connection->query($select);
                while ($row = $cursor->fetch()) {
                    $key = implode('-', [$storeViewCode, $row['entity_id'], $row['attribute_code']]);
                    $output[$key]['productId'] = $row['entity_id'];
                    $output[$key]['storeViewCode'] = $storeViewCode;
                    $output[$key]['attributes'] = [
                        'attributeCode' => $row['attribute_code'],
                        'value' => ($row['value'] !== null) ?
                            $this->attributeMetadata->getAttributeValue(
                                $row['attribute_code'],
                                $storeViewCode,
                                $row['value']
                            ) : null
                    ];
                }
            }
        } catch (\Exception $exception) {
            throw new UnableRetrieveData(
                sprintf('Unable to retrieve attributes data: %s', $exception->getMessage()),
                0,
                $exception
            );
        }
        return $output;
    }
}
