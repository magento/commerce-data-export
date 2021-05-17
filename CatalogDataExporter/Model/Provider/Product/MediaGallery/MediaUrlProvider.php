<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product\MediaGallery;

use Magento\Catalog\Model\Product\Media\ConfigInterface as MediaConfig;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Provider for base media urls
 */
class MediaUrlProvider
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var MediaConfig
     */
    private $mediaConfig;

    /**
     * @var string[]
     */
    private $baseMediaUrlsCache;

    /**
     * @param StoreManagerInterface $storeManager
     * @param MediaConfig $mediaConfig
     */
    public function __construct(StoreManagerInterface $storeManager, MediaConfig $mediaConfig)
    {
        $this->storeManager = $storeManager;
        $this->mediaConfig = $mediaConfig;
    }

    /**
     * Get base media url by store view code
     *
     * @param string $storeViewCode
     *
     * @return string
     *
     * @throws NoSuchEntityException
     */
    public function getBaseMediaUrlByStoreViewCode(string $storeViewCode): string
    {
        if (!isset($this->baseMediaUrlsCache[$storeViewCode])) {
            $this->baseMediaUrlsCache[$storeViewCode] = \sprintf(
                '%s%s',
                $this->storeManager->getStore($storeViewCode)->getBaseUrl(UrlInterface::URL_TYPE_MEDIA),
                $this->mediaConfig->getBaseMediaPath()
            );
        }

        return $this->baseMediaUrlsCache[$storeViewCode];
    }
}
