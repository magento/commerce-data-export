<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Category;

use Magento\Catalog\Model\Category\FileInfo;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Category image data provider
 */
class Image
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var FileInfo
     */
    private $fileInfo;

    /**
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param FileInfo $fileInfo
     */
    public function __construct(LoggerInterface $logger, StoreManagerInterface $storeManager, FileInfo $fileInfo)
    {
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->fileInfo = $fileInfo;
    }

    /**
     * Get provider data
     *
     * @param array $values
     *
     * @return array
     *
     * @throws UnableRetrieveData
     * @throws NoSuchEntityException
     */
    public function get(array $values) : array
    {
        // phpcs:disable
        $output = [];
        $actualStoreCode = $this->storeManager->getStore()->getCode();

        try {
            foreach ($values as $value) {
                $imageUrl = $value['image'];

                if (null === $imageUrl) {
                    continue;
                }

                $this->storeManager->setCurrentStore($value['storeViewCode']);

                $mediaBaseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

                if ($this->fileInfo->isBeginsWithMediaDirectoryPath($imageUrl)) {
                    $imageUrl = \sprintf(
                        '%s/%s',
                        \rtrim($mediaBaseUrl, '/'),
                        \ltrim($this->fileInfo->getRelativePathToMediaDirectory($imageUrl), '/')
                    );
                } elseif (\substr($imageUrl, 0, 1) !== '/') {
                    $imageUrl = \sprintf(
                        '%s/%s/%s',
                        \rtrim($mediaBaseUrl, '/'),
                        \ltrim(
                            FileInfo::ENTITY_MEDIA_PATH, '/'),
                        \basename($imageUrl)
                    );
                }

                $output[] = [
                    'categoryId' => $value['categoryId'],
                    'storeViewCode' => $value['storeViewCode'],
                    'image' => $imageUrl,
                ];
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve category image');
        } finally {
            $this->storeManager->setCurrentStore($actualStoreCode);
        }

        // phpcs:enable
        return $output;
    }
}
