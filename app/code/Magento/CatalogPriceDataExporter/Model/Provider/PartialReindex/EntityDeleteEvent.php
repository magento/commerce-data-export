<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Provider\PartialReindex;

use Magento\CatalogDataExporter\Model\Provider\Product\ProductOptions\OptionValueUidInterface;
use Magento\CatalogPriceDataExporter\Model\EventKeyGenerator;
use Magento\CatalogPriceDataExporter\Model\Query\EntityDelete;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class responsible for providing product options and links delete price events
 */
class EntityDeleteEvent implements PartialReindexPriceProviderInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var EntityDelete
     */
    private $entityDelete;

    /**
     * @var EventKeyGenerator
     */
    private $eventKeyGenerator;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $eventType;

    /**
     * @var array|null
     */
    private $uidResolverData;

    /**
     * @param ResourceConnection $resourceConnection
     * @param EntityDelete $entityDelete
     * @param EventKeyGenerator $eventKeyGenerator
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param string $eventType
     * @param array|null $uidResolverData
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        EntityDelete $entityDelete,
        EventKeyGenerator $eventKeyGenerator,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        string $eventType,
        ?array $uidResolverData = null
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->entityDelete = $entityDelete;
        $this->eventKeyGenerator = $eventKeyGenerator;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->eventType = $eventType;
        $this->uidResolverData = $uidResolverData;
    }

    /**
     * @inheritdoc
     */
    public function retrieve(array $indexData): \Generator
    {
        try {
            foreach (array_chunk($indexData, self::BATCH_SIZE) as $batchedData) {
                $result = [];
                $queryArguments = [];
                foreach ($batchedData as $data) {
                    $queryArguments[] = $data['entity_id'];
                }
                $select = $this->entityDelete->getQuery($queryArguments);
                $cursor = $this->resourceConnection->getConnection()->query($select);
                while ($row = $cursor->fetch()) {
                    $result[$row['entity_id']] = $row;
                }
                yield $this->getEventsData($batchedData, $result);
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve entity delete event data.');
        }
    }

    /**
     * Retrieve prices event data
     *
     * @param array $indexData
     * @param array $actualData
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     * @throws LocalizedException
     */
    private function getEventsData(array $indexData, array $actualData): array
    {
        $events = [];

        foreach ($indexData as $data) {
            if (!isset($actualData[$data['entity_id']])) {
                $websiteId = (string)$this->storeManager->getWebsite(WebsiteInterface::ADMIN_CODE)->getWebsiteId();
                $key = $this->eventKeyGenerator->generate($this->eventType, $websiteId, null);
                $events[$key][] = ['id' => $this->resolveId($data)];
            }
        }

        return $events;
    }

    /**
     * Resolve price entity id
     *
     * @param array $indexData
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    private function resolveId(array $indexData): string
    {
        if ($this->uidResolverData === null) {
            return $indexData['entity_id'];
        }

        $parameters = [];
        foreach ($this->uidResolverData['parameters'] as $param) {
            $parameters[$param['parameterKey']] = $indexData[$param['valueKey']];
        }

        /* @var OptionValueUidInterface $resolver */
        $resolver = $this->uidResolverData['class'];

        return $resolver->resolve($parameters);
    }
}
