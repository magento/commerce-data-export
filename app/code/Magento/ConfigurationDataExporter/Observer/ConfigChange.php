<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurationDataExporter\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;

class ConfigChange implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\ConfigurationDataExporter\Api\ConfigRegistryInterface
     */
    private $configRegistry;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @param \Magento\ConfigurationDataExporter\Api\ConfigRegistryInterface $configRegistry
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory $collectionFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\ConfigurationDataExporter\Api\ConfigRegistryInterface $configRegistry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory $collectionFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->configRegistry = $configRegistry;
        $this->storeManager = $storeManager;
        $this->collectionFactory = $collectionFactory;
        $this->logger = $logger;
    }

    /**
     * Log out user and redirect to new admin custom url
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Framework\App\Config\Value $configData */
        $configData = $observer->getData('data_object');

        if ($configData->isValueChanged()) {
            $scope = (string)$configData->getScope();
            $path = (string)$configData->getPath();
            $scopeId = (int)$configData->getScopeId();
            $value = $configData->getValue();

            if (in_array($scope, [ScopeInterface::SCOPE_STORE, ScopeInterface::SCOPE_STORES])) {
                // if config changed on store view scope - just add to registry
                $this->configRegistry->addValue([
                    'path' => $path,
                    'value' => $value,
                    'scope_id' => $scopeId
                ]);

                return;
            }

            try {
                foreach ($this->getStoreIdsForUpdate($scope, $scopeId, $path) as $storeId) {
                    $this->configRegistry->addValue([
                        'path' => $configData->getPath(),
                        'value' => $value,
                        'scope_id' => $storeId
                    ]);
                }
            } catch (LocalizedException $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }

    /**
     * Get store ids that need to be updated with new config value.
     *
     * @param string $scope
     * @param int $scopeId
     * @param string $path
     * @return array|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getStoreIdsForUpdate(string $scope, int $scopeId, string $path): array
    {
        $collection = $this->collectionFactory->create();

        if ($scope === ScopeConfigInterface::SCOPE_TYPE_DEFAULT) {
            $storeIds = array_keys($this->storeManager->getStores());
            $connection = $collection->getConnection();
            $select = $connection->select()
                ->distinct()
                ->from(
                    ['s' => $connection->getTableName('store')],
                    ['store_id']
                )->joinLeft(
                    ['c' => $connection->getTableName('core_config_data')],
                    '(s.store_id = c.scope_id AND c.scope = "stores") OR '.
                    '(s.website_id = c.scope_id AND c.scope = "websites")',
                    []
                )->where('c.path = ?', $path);

            $excludeStoreIds = $connection->fetchCol($select);
        } else {
            $storeIds = $this->storeManager->getWebsite($scopeId)->getStoreIds();
            $excludeStoreIds = $collection->addFieldToFilter('path', $path)
                ->addFieldToFilter('scope', ScopeInterface::SCOPE_STORES)
                ->addFieldToFilter('scope_id', ['in' => $storeIds])
                ->getColumnValues('scope_id');
        }

        return array_diff($storeIds, $excludeStoreIds);
    }
}
