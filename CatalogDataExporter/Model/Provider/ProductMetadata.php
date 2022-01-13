<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider;

use Magento\CatalogDataExporter\Model\Provider\Product\Formatter\FormatterInterface;
use Magento\CatalogDataExporter\Model\Query\ProductMetadataQuery;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * Product attributes metadata provider
 */
class ProductMetadata
{
    /**
     * Category EAV entity type id
     */
    private const CATEGORY_EAV_ENTITY_TYPE_ID = 3;

    /**
     * Category EAV entity type
     */
    private const CATEGORY_EAV_ENTITY_TYPE = 'catalog_category';

    /**
     * Product EAV entity type id
     */
    private const PRODUCT_EAV_ENTITY_TYPE = 'catalog_product';

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ProductMetadataQuery
     */
    private $productMetadataQuery;

    /**
     * @var FormatterInterface
     */
    private $formatter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ProductMetadataQuery $productMetadataQuery
     * @param FormatterInterface $formatter
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ProductMetadataQuery $productMetadataQuery,
        FormatterInterface $formatter,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->productMetadataQuery = $productMetadataQuery;
        $this->formatter = $formatter;
        $this->logger = $logger;
    }

    /**
     * Format provider data
     *
     * @param array $row
     * @return array
     */
    private function format(array $row) : array
    {
        $output = $row;
        $output = $this->formatter->format($output);

        if (true === $output['boolean']) {
            $output['numeric'] = false;
        }

        // we only retrieve catalog eav attributes (product and category attributes only) in query
        $output['attributeType'] = ((int)$output['entityTypeId'] === self::CATEGORY_EAV_ENTITY_TYPE_ID) ?
                                        self::CATEGORY_EAV_ENTITY_TYPE : self::PRODUCT_EAV_ENTITY_TYPE;

        return $output;
    }

    /**
     * Returns attribute data
     *
     * @param array $values
     * @return array
     * @throws UnableRetrieveData
     */
    public function get(array $values): array
    {
        $output = [];
        $queryArguments = [];
        try {
            foreach ($values as $value) {
                $queryArguments['id'][$value['id']] = $value['id'];
            }
            $connection = $this->resourceConnection->getConnection();
            $select = $this->productMetadataQuery->getQuery($queryArguments);
            $cursor = $connection->query($select);
            while ($row = $cursor->fetch()) {
                $output[] = $this->format($row);
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            throw new UnableRetrieveData('Unable to retrieve product data');
        }
        return $output;
    }
}
