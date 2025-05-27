<?php
/**
 * Copyright 2024 Adobe
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

namespace Magento\CatalogDataExporter\Model\Provider;

use Magento\CatalogDataExporter\Model\Provider\Product\Formatter\FormatterInterface;
use Magento\CatalogDataExporter\Model\Query\ProductMetadataQuery;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\DataExporter\Export\DataProcessorInterface;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\App\ResourceConnection;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * Product attributes metadata provider
 */
class ProductMetadata implements DataProcessorInterface
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
     * @inheritdoc
     *
     * @throws UnableRetrieveData
     * @throws \Zend_Db_Statement_Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(
        array $arguments,
        callable $dataProcessorCallback,
        FeedIndexMetadata $metadata,
        $node = null,
        $info = null
    ): void {
        $output = [];
        $queryArguments = [];
        try {
            foreach ($arguments as $value) {
                $queryArguments['id'][$value['id']] = $value['id'];
            }
            $connection = $this->resourceConnection->getConnection();
            $select = $this->productMetadataQuery->getQuery($queryArguments);
            $cursor = $connection->query($select);
            while ($row = $cursor->fetch()) {
                $output[] = $this->format($row);
            }
        } catch (\Throwable $exception) {
            throw new UnableRetrieveData(
                sprintf('Unable to retrieve product data: %s', $exception->getMessage()),
                0,
                $exception
            );
        }

        $dataProcessorCallback($this->get($output));
    }

    /**
     * For backward compatibility with existing 3-rd party plugins.
     *
     * @param array $values
     * @return array
     * @deprecated
     * @see self::execute
     */
    public function get(array $values) : array
    {
        return $values;
    }
}
