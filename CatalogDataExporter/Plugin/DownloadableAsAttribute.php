<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Plugin;

use Magento\DataExporter\Export\Processor;
use Magento\Downloadable\Model\Product\Type;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * For downloadable products, adds samples and links to product.attributes[code="ac_downloadable"].
 *
 * Intentionally keep logic in plugin to simplify future refactoring: eventually legacy approach for downloadable
 * product would be eliminated.
 */
class DownloadableAsAttribute
{
    private const DOWNLOADABLE_ATTRIBUTE_CODE = 'ac_downloadable';
    private const OPTION_TYPE = 'downloadable';

    /**
     * @param SerializerInterface $serializer
     */
    public function __construct(
        private readonly SerializerInterface $serializer
    ) {}

    /**
     * After process plugin for the Processor class.
     *
     * @param Processor $processor
     * @param array $feedItems
     * @param string $feedName
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterProcess(Processor $processor, array $feedItems, string $feedName): array
    {
        if ($feedName === 'products') {
            $this->addAttributeToProductFeed($feedItems);
        }

        return $feedItems;
    }

    /**
     * Adds the downloadable attribute to the product feed items.
     *
     * @param array $products
     * @return void
     */
    private function addAttributeToProductFeed(array &$products): void
    {
        foreach ($products as &$product) {
            if ($product['type'] === Type::TYPE_DOWNLOADABLE) {
                $downloadableAttributeData = $this->buildAttributeData($product);
                if ($downloadableAttributeData) {
                    $product['attributes'][] = [
                        'attributeCode' => self::DOWNLOADABLE_ATTRIBUTE_CODE,
                        'value' => [$downloadableAttributeData],
                    ];
                }
            }
        }
    }

    /**
     * Builds the downloadable attribute data for a product.
     *
     * @param array $product
     * @return string|null
     */
    private function buildAttributeData(array &$product): ?string
    {
        return $this->serializer->serialize([
            'purchase_separately' => (bool)($product['linksPurchasedSeparately'] ?? false),
            'samples' => $this->getSamples($product['samples'] ?? []),
            'links' => $this->getLinks($product['optionsV2'] ?? [])
        ]);
    }

    /**
     * Extracts sample links from product samples.
     *
     * @param array $samples
     * @return array
     */
    private function getSamples(array $samples): array
    {
        $output = [];
        usort($samples, function ($a, $b) {
            return ($a['sortOrder'] ?? 0) <=> ($b['sortOrder'] ?? 0);
        });

        foreach ($samples as $sampleLink) {
            if (!isset($sampleLink['resource'])) {
                continue;
            }
            $output[] = [
                'label' => $sampleLink['resource']['label'],
                'url' => $sampleLink['resource']['url'],
            ];

        }
        return $output;
    }

    /**
     * Extracts downloadable links from product options.
     *
     * @param array $options
     * @return array
     */
    private function getLinks(array $options): array
    {
        $links = [];
        foreach ($options as $option) {
            if ($option['type'] !== self::OPTION_TYPE) {
                continue;
            }
            $values = $option['values'] ?? [];
            usort($values, function ($a, $b) {
                return ($a['sortOrder'] ?? 0) <=> ($b['sortOrder'] ?? 0);
            });

            foreach ($values as $n => $value) {
                $links[] = [
                    'uid' => $value['id'],
                    'label' => $value['label'] ?? __('Link') . ' ' . $n,
                    'price' => $value['price'] ?? 0,
                    'number_of_downloads' => $value['qty'] ?? 0,
                    'sample_url' => $value['infoUrl']
                ];
            }
        }
        return $links;
    }
}
