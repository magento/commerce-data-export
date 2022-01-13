<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Model\Provider;

use Magento\ProductVariantDataExporter\Model\Provider\ProductVariants\ConfigurableId;
use Magento\ProductVariantDataExporter\Model\Provider\ProductVariants\IdFactory;
use Magento\ProductVariantDataExporter\Model\Provider\ProductVariants\OptionValueFactory;
use Magento\ProductVariantDataExporter\Model\Query\ProductVariantsQuery;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * Configurable product variants provider
 */
class ConfigurableVariants implements ProductVariantsProviderInterface
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
     * @inheritDoc
     *
     * @throws UnableRetrieveData
     */
    public function get(array $values): array
    {
        $output = [];
        $childIds = [];
        foreach ($values as $value) {
            $childIds[$value['id']] = $value['id'];
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
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            throw new UnableRetrieveData('Unable to retrieve configurable product variants data');
        }
        return $output;
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
            if(isset($row['optionValueId']) && isset($row['attributeCode'])) {
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
}
