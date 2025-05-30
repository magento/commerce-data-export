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

namespace Magento\ProductVariantDataExporter\Model\Provider;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\ProductVariantDataExporter\Model\Provider\ProductVariants\ConfigurableId;
use Magento\ProductVariantDataExporter\Model\Provider\ProductVariants\IdFactory;
use Magento\ProductVariantDataExporter\Model\Provider\ProductVariants\OptionValueFactory;
use Magento\ProductVariantDataExporter\Model\Query\ProductVariantsQuery;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;
use Magento\DataExporter\Export\DataProcessorInterface;

/**
 * Configurable product variants provider
 */
class ConfigurableVariants implements ProductVariantsProviderInterface, DataProcessorInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ProductVariantsQuery
     */
    private $variantsOptionValuesQuery;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var OptionValueFactory
     */
    private $optionValueFactory;

    /**
     * @var IdFactory
     */
    private $idFactory;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ProductVariantsQuery $variantsOptionValuesQuery
     * @param OptionValueFactory $optionValueFactory
     * @param IdFactory $idFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ProductVariantsQuery $variantsOptionValuesQuery,
        OptionValueFactory $optionValueFactory,
        IdFactory $idFactory,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->variantsOptionValuesQuery = $variantsOptionValuesQuery;
        $this->logger = $logger;
        $this->optionValueFactory = $optionValueFactory;
        $this->idFactory = $idFactory;
    }

    /**
     * {@inheritdoc}
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
        $childIds = [];
        foreach ($arguments as $value) {
            $childIds[$value['productId']] = $value['productId'];
        }

        try {
            $variants = $this->getVariants($childIds);
            foreach ($variants as $id => $optionValues) {
                $output[] = [
                    'id' => $id,
                    'optionValues' => $optionValues['optionValues'],
                    'parentId' => $optionValues['parentId'],
                    'productId' => $optionValues['childId'],
                    'parentSku' => $optionValues['parentSku'],
                    'productSku' => $optionValues['productSku']
                ];
            }
        } catch (\Throwable $exception) {
            throw new UnableRetrieveData(
                sprintf('Unable to retrieve configurable product variants data: %s', $exception->getMessage()),
                0,
                $exception
            );
        }

        $dataProcessorCallback($this->get($output));
    }

    /**
     * Get configurable product variants
     *
     * @param array $childIds
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    private function getVariants(array $childIds): array
    {
        $variants = [];
        $idResolver = $this->idFactory->get('configurable');
        $optionValueResolver = $this->optionValueFactory->get('configurable');

        $cursor = $this->resourceConnection->getConnection()->query(
            $this->variantsOptionValuesQuery->getQuery($childIds)
        );
        while ($row = $cursor->fetch()) {
            $id = $idResolver->resolve([
                ConfigurableId::PARENT_SKU_KEY => $row['parentSku'],
                ConfigurableId::CHILD_SKU_KEY => $row['productSku']
            ]);
            if (isset($row['optionValueId'], $row['attributeCode'])) {
                $optionValue = $optionValueResolver->resolve($row);
                $variants[$id]['parentId'] = $row['parentId'];
                $variants[$id]['childId'] = $row['childId'];
                $variants[$id]['parentSku'] = $row['parentSku'];
                $variants[$id]['productSku'] = $row['productSku'];
                $variants[$id]['optionValues'][] = $optionValue;
            }
        }
        return $variants;
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
