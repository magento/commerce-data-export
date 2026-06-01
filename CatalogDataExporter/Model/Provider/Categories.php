<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider;

use Magento\CatalogDataExporter\Model\Provider\Category\AncestorStatusProvider;
use Magento\CatalogDataExporter\Model\Provider\Category\CategoryUrlPathBuilder;
use Magento\CatalogDataExporter\Model\Provider\Category\Formatter\FormatterInterface;
use Magento\CatalogDataExporter\Model\Provider\EavAttributes\EntityEavAttributesResolver;
use Magento\CatalogDataExporter\Model\Query\CategoryMainQuery;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\DataExporter\Export\DataProcessorInterface;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\Store;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * Categories main data provider
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Categories implements DataProcessorInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var CategoryMainQuery
     */
    private $categoryMainQuery;

    /**
     * @var FormatterInterface
     */
    private $formatter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EntityEavAttributesResolver
     */
    private $entityEavAttributesResolver;

    /**
     * @var CategoryUrlPathBuilder
     */
    private $urlPathBuilder;

    /**
     * @var AncestorStatusProvider
     */
    private $ancestorStatusProvider;

    /**
     * @param ResourceConnection $resourceConnection
     * @param CategoryMainQuery $categoryMainQuery
     * @param FormatterInterface $formatter
     * @param LoggerInterface $logger
     * @param EntityEavAttributesResolver $entityEavAttributesResolver
     * @param CategoryUrlPathBuilder|null $urlPathBuilder
     * @param AncestorStatusProvider|null $ancestorStatusProvider
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        CategoryMainQuery $categoryMainQuery,
        FormatterInterface $formatter,
        LoggerInterface $logger,
        EntityEavAttributesResolver $entityEavAttributesResolver,
        ?CategoryUrlPathBuilder $urlPathBuilder = null,
        ?AncestorStatusProvider $ancestorStatusProvider = null
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->categoryMainQuery = $categoryMainQuery;
        $this->formatter = $formatter;
        $this->logger = $logger;
        $this->entityEavAttributesResolver = $entityEavAttributesResolver;
        $this->urlPathBuilder = $urlPathBuilder ?? ObjectManager::getInstance()->get(CategoryUrlPathBuilder::class);
        $this->ancestorStatusProvider = $ancestorStatusProvider
            ?? ObjectManager::getInstance()->get(AncestorStatusProvider::class);
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
        $info = null,
        $lastChunk = null
    ): void {
        try {
            foreach ($this->getDataBatch($arguments, $metadata->getBatchSize()) as $dataBatch) {
                $output = [];
                [$mappedCategories, $attributesData] = $dataBatch;
                foreach ($mappedCategories as $storeCode => $categories) {
                    $merged = \array_replace_recursive(
                        $categories,
                        $this->entityEavAttributesResolver->resolve($attributesData[$storeCode], $storeCode)
                    );
                    $merged = $this->injectUrlPaths($merged);
                    \array_push($output, ...\array_map($this->formatter->format(...), $merged));
                }
                $output = $this->applyAncestorStatus($output);
                $dataProcessorCallback($this->get($output));
            }
        } catch (\Throwable $exception) {
            throw new UnableRetrieveData(
                sprintf('Unable to retrieve category data: %s', $exception->getMessage()),
                0,
                $exception
            );
        }
    }

    /**
     * For backward compatibility - to allow 3rd party plugins work
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

    /**
     * Returns data batch.
     *
     * @param array $arguments
     * @param int $batchSize
     * @return \Generator
     * @throws \Zend_Db_Statement_Exception
     */
    private function getDataBatch(array $arguments, int $batchSize): \Generator
    {
        $itemN = 0;
        $queryArguments = [];
        $mappedCategories = [];
        $attributesData = [];
        foreach ($arguments as $value) {
            $scope = $value['scopeId'] ?? Store::DEFAULT_STORE_ID;
            $queryArguments[$scope][$value['categoryId']] = $value['attribute_ids'] ?? [];
        }

        $connection = $this->resourceConnection->getConnection();
        foreach ($queryArguments as $scopeId => $categoryData) {
            $cursor = $connection->query(
                $this->categoryMainQuery->getQuery(\array_keys($categoryData), $scopeId ?: null)
            );

            while ($row = $cursor->fetch()) {
                $itemN++;
                $mappedCategories[$row['storeViewCode']][$row['categoryId']] = $row;
                $attributesData[$row['storeViewCode']][$row['categoryId']] = $categoryData[$row['categoryId']];
                if ($itemN % $batchSize == 0) {
                    yield [$mappedCategories,  $attributesData];
                    $mappedCategories = [];
                    $attributesData = [];
                }
            }
        }

        yield [$mappedCategories, $attributesData];
    }

    /**
     * Computes urlPath from url_key EAV values and injects it into each category row.
     *
     * This replaces the stored url_path EAV value, which may be absent or stale after a category
     * move due to a core bug in Category::move() that deletes url_path EAV rows for all stores
     * except the last one in getStoreIds().
     *
     * The storeId and rootCategoryId fields (from CategoryMainQuery) are internal and are removed
     * from the row before it reaches the formatter.
     *
     * @param array $categories  [categoryId => row] for a single store view
     * @return array
     */
    private function injectUrlPaths(array $categories): array
    {
        if (empty($categories)) {
            return $categories;
        }

        $firstRow = reset($categories);
        $storeViewCode = $firstRow['storeViewCode'];

        $pathsByEntityId = array_map(fn(array $row): string => $row['path'], $categories);
        $urlPaths = $this->urlPathBuilder->resolveUrlPaths($pathsByEntityId, $storeViewCode);

        foreach ($categories as $categoryId => &$categoryData) {
            $path = $urlPaths[(int)$categoryId] ?? '';
            // keep 1st level category without url key/path (like 1/2 "Default Category")
            // for backward compatibility but remove empty path
            if (!$path && $categoryData['level'] > 1) {
                $this->logger->error(sprintf(
                    'CDE01-21 Unable to resolve url_path for category %d with path "%s", url_key "%s", store "%s"',
                    $categoryData['categoryId'],
                    $categoryData['path'] ?? '',
                    $categoryData['urlKey'] ?? '',
                    $storeViewCode
                ));
                unset($categoryData['storeId'], $categoryData['rootCategoryId']);
                continue;
            }
            $categoryData['urlPath'] = $path;
            unset($categoryData['storeId'], $categoryData['rootCategoryId']);
        }

        return $categories;
    }

    /**
     * Overrides isActive and includeInMenu for categories whose ancestors are inactive or excluded from menu.
     *
     * AC hide all children categories if top-level (and only top-level) menu not active or not included in menu
     *
     * @param array $output
     * @return array
     */
    private function applyAncestorStatus(array $output): array
    {
        foreach ($output as &$category) {
            $ancestorId = $this->getAncestorId($category['path'] ?? '');
            if (!$ancestorId) {
                continue;
            }
            $status = $this->ancestorStatusProvider->getAncestorStatus($ancestorId, $category['storeViewCode']);
            if (!$status['isActive']) {
                $category['isActive'] = false;
            }
            if (!$status['includeInMenu']) {
                $category['includeInMenu'] = false;
            }
        }
        return $output;
    }

    /**
     * Returns the top-level menu category ID as the sole ancestor to check.
     *
     * For path 1/2/3/4 returns 3. Returns null when the category itself is top-level (path 1/2/3).
     *
     * @param string $path
     * @return int|null
     */
    private function getAncestorId(string $path): ?int
    {
        $parts = explode('/', $path);
        return isset($parts[3]) ? (int)$parts[2] : null;
    }
}
