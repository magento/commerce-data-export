<?php
/**
 * Copyright 2022 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Category;

use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;

/**
 * TODO: Ad-hoc solution, will be refactored
 */
class Children
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param LoggerInterface $logger
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        LoggerInterface $logger,
        CollectionFactory $collectionFactory
    ) {
        $this->logger = $logger;
        $this->collectionFactory = $collectionFactory;
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
        try {
            $output = [];

            foreach ($values as $value) {
                // TODO: et_schema does not allow return array of scalars.
                // @See \Magento\DataExporter\Export\Extractor::indexDataByArguments
                foreach ($this->getChildren($value['categoryId']) as $id) {
                    $output[(string)uniqid('s', true)] = [
                        'categoryId' => $value['categoryId'],
                        'storeViewCode' => $value['storeViewCode'],
                        'children' => $id
                    ];
                }
            }
        } catch (\Throwable $exception) {
            throw new UnableRetrieveData(
                sprintf('Unable to retrieve category children: %s', $exception->getMessage()),
                0,
                $exception
            );
        }

        return $output;
    }

    /**
     * Retrieve list of children
     *
     * @param int $categoryId
     * @return array
     */
    private function getChildren($categoryId): array
    {
        $category = $this->collectionFactory->create();
        $category->addIdFilter($categoryId);
        $category->load();
        return $category->getFirstItem()->getChildrenCategories()->getAllIds();
    }
}
