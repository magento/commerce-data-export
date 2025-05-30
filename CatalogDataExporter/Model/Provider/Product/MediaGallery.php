<?php
/**
 * Copyright 2022 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product;

use Magento\CatalogDataExporter\Model\Provider\Product\MediaGallery\MediaGalleryFormatterInterface;
use Magento\CatalogDataExporter\Model\Query\MediaGalleryQueryBuilder;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * Product media gallery data provider
 */
class MediaGallery
{
    /**
     * Image codes mapping
     *
     * @var string[]
     */
    private static $imagesMapping = [
        'image' => 'image',
        'smallImage' => 'small_image',
        'thumbnail' => 'thumbnail',
        'swatchImage' => 'swatch_image',
    ];

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var MediaGalleryQueryBuilder
     */
    private $mediaGalleryQueryBuilder;

    /**
     * @var MediaGalleryFormatterInterface
     */
    private $mediaGalleryFormatter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string|null
     */
    private $mediaType;

    /**
     * @param ResourceConnection $resourceConnection
     * @param MediaGalleryQueryBuilder $mediaGalleryQueryBuilder
     * @param MediaGalleryFormatterInterface $mediaGalleryFormatter
     * @param LoggerInterface $logger
     * @param string|null $mediaType
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        MediaGalleryQueryBuilder $mediaGalleryQueryBuilder,
        MediaGalleryFormatterInterface $mediaGalleryFormatter,
        LoggerInterface $logger,
        ?string $mediaType = null
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->mediaGalleryQueryBuilder = $mediaGalleryQueryBuilder;
        $this->mediaGalleryFormatter = $mediaGalleryFormatter;
        $this->logger = $logger;
        $this->mediaType = $mediaType;
    }

    /**
     * Get provider data
     *
     * @param array $values
     *
     * @return array
     *
     * @throws UnableRetrieveData
     */
    public function get(array $values) : array
    {
        $queryArguments = [];
        $output = [];
        $roleImages = [];

        foreach ($values as $value) {
            $queryArguments[$value['storeViewCode']][$value['productId']] = $value['productId'];

            foreach (self::$imagesMapping as $key => $code) {
                $roleImages[$value['storeViewCode']][$value['productId']][$code] = $value[$key . '_default'] ?? '';
            }
        }

        try {
            $connection = $this->resourceConnection->getConnection();

            foreach ($queryArguments as $storeViewCode => $productIds) {
                $cursor = $connection->query(
                    $this->mediaGalleryQueryBuilder->getQuery($productIds, $storeViewCode, $this->mediaType)
                );

                while ($row = $cursor->fetch()) {
                    $output[] = $this->mediaGalleryFormatter->format($row, $roleImages);
                }
            }
        } catch (\Throwable $exception) {
            throw new UnableRetrieveData(
                sprintf('Unable to retrieve product media gallery: %s', $exception->getMessage()),
                0,
                $exception
            );
        }

        return $output;
    }
}
