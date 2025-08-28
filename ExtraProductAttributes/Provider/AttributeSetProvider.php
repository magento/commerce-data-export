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


use AdobeCommerce\ExtraProductAttributes\Provider\Query\AttributeSetQuery;
use Magento\Framework\App\ResourceConnection;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * Get product attribute set data
 */
class AttributeSetProvider
{
    /**
     * @param ResourceConnection $resourceConnection
     * @param AttributeSetQuery $query
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly AttributeSetQuery $query,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Returned format:
     *  [
     *    <productId> => [
     *          id => <attributeSetId>,
     *          name => <attributeSetName>
     *     ],
     *  ]
     */
    public function execute(array $productIds): array {
        $connection = $this->resourceConnection->getConnection();
        $output = [];

        try {
            $select = $this->query->getQuery($productIds);
            $cursor = $connection->query($select);
            while ($row = $cursor->fetch()) {
                $output[$row['productId']] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                ];
            }
        } catch (\Throwable $e) {
            $this->logger->error('Attribute Set export error: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
        return $output;
    }
}
