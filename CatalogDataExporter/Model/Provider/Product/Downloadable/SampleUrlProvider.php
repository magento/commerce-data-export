<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product\Downloadable;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Provider for base downloadable product sample url
 */
class SampleUrlProvider
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var string
     */
    private $baseUrlPath;

    /**
     * @var string
     */
    private $sampleIdentity;

    /**
     * @var string[]
     */
    private $baseSampleUrlsCache;

    /**
     * @param StoreManagerInterface $storeManager
     * @param string $baseUrlPath
     * @param string $sampleIdentity
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        string $baseUrlPath = 'downloadable/download/sample',
        string $sampleIdentity = 'sample_id'
    ) {
        $this->storeManager = $storeManager;
        $this->baseUrlPath = $baseUrlPath;
        $this->sampleIdentity = $sampleIdentity;
    }

    /**
     * Retrieve base sample url by store view code
     *
     * @param string $storeViewCode
     *
     * @return string
     *
     * @throws NoSuchEntityException
     */
    public function getBaseSampleUrlByStoreViewCode(string $storeViewCode): string
    {
        if (!isset($this->baseSampleUrlsCache[$storeViewCode])) {
            $this->baseSampleUrlsCache[$storeViewCode] = \sprintf(
                '%s%s/%s/',
                $this->storeManager->getStore($storeViewCode)->getBaseUrl(UrlInterface::URL_TYPE_WEB),
                $this->baseUrlPath,
                $this->sampleIdentity
            );
        }

        return $this->baseSampleUrlsCache[$storeViewCode];
    }
}
