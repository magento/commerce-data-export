<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product\Formatter;

use Magento\Catalog\Model\Product\Media\ConfigInterface as MediaConfig;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * Image formatter for product provider
 * @deprecated "role-based" images will be removed from product level
 */
class ImageFormatter implements FormatterInterface
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $images;

    /**
     * @var string[]
     */
    private $baseMediaUrlCache;

    /**
     * @param StoreManagerInterface $storeManager
     * @param MediaConfig $mediaConfig
     * @param LoggerInterface $logger
     * @param array $images
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        MediaConfig $mediaConfig,
        LoggerInterface $logger,
        array $images = [
            'image',
            'smallImage',
            'thumbnail',
            'swatchImage',
        ]
    ) {
        $this->storeManager = $storeManager;
        $this->mediaConfig = $mediaConfig;
        $this->logger = $logger;
        $this->images = $images;
    }

    /**
     * Format provider data
     *
     * @param array $row
     *
     * @return array
     *
     * @throws UnableRetrieveData
     */
    public function format(array $row) : array
    {
        try {
            foreach ($this->images as $image) {
                if (isset($row[$image])) {
                    $row[$image . '_default'] = $row[$image];
                    $row[$image] = [
                        'url' => $this->getBaseMediaUrlByStoreViewCode($row['storeViewCode']) . $row[$image],
                        'label' => $row[$image . 'Label'] ?? null,
                    ];
                }
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            throw new UnableRetrieveData('Unable to retrieve product formatted image data');
        }

        return $row;
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
    private function getBaseMediaUrlByStoreViewCode(string $storeViewCode) : string
    {
        if (!isset($this->baseMediaUrlCache[$storeViewCode])) {
            $this->baseMediaUrlCache[$storeViewCode] = \sprintf(
                '%s%s',
                $this->storeManager->getStore($storeViewCode)->getBaseUrl(UrlInterface::URL_TYPE_MEDIA),
                $this->mediaConfig->getBaseMediaPath()
            );
        }

        return $this->baseMediaUrlCache[$storeViewCode];
    }
}
