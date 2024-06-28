<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurableProductDataExporter\Model\Provider\Product;

use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProductDataExporter\Model\Query\VariantsQuery;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Configurable product variant data provider
 *  TODO: Deprecated - remove this class and its query. https://github.com/magento/catalog-storefront/issues/419
 */
class Variants
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var VariantsQuery
     */
    private $variantQuery;

    /**
     * @var Config
     */
    private Config $eavConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ?int
     */
    private ?int $statusAttributeId = null;

    /**
     * Variants constructor.
     * @param ResourceConnection $resourceConnection
     * @param VariantsQuery $variantQuery
     * @param ?Config $eavConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        VariantsQuery $variantQuery,
        ?Config $eavConfig,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->variantQuery = $variantQuery;
        $this->eavConfig = $eavConfig ?? ObjectManager::getInstance()->get(Config::class);
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
            $output = [];
            foreach ($values as $value) {
                if (!isset($value['productId'], $value['type'], $value['storeViewCode'])
                    || $value['type'] !== Configurable::TYPE_CODE) {
                    continue;
                }
                $queryArguments['productId'][$value['productId']] = $value['productId'];
                $queryArguments['storeViewCode'][$value['storeViewCode']] = $value['storeViewCode'];
            }

            if (!$queryArguments) {
                return $output;
            }
            $select = $this->variantQuery->getQuery($queryArguments, $this->getStatusAttributeId());
            $cursor = $connection->query($select);
            while ($row = $cursor->fetch()) {
                $key = $row['sku'] . '-' . $row['storeViewCode'];
                $output[$key]['variants']['sku'] = $row['sku'];
                $output[$key]['productId'] = $row['productId'];
                $output[$key]['storeViewCode'] = $row['storeViewCode'];
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            throw new UnableRetrieveData('Unable to retrieve product variant data');
        }
        return array_values($output);
    }

    /**
     * Get status attribute id and cache it
     */
    private function getStatusAttributeId(): ?int
    {
        try {
            if ($this->statusAttributeId === null) {
                $attribute = $this->eavConfig->getAttribute(Product::ENTITY, 'status');
                $this->statusAttributeId = $attribute ? (int)$attribute->getId() : null;
            }
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);

        }

        return $this->statusAttributeId;
    }
}
