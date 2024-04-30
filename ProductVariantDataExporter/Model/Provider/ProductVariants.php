<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Model\Provider;

use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\DataExporter\Export\DataProcessorInterface;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;

/**
 * Product variants data provider
 */
class ProductVariants implements DataProcessorInterface
{
    /**
     * @var DataProcessorInterface[]
     */
    private $variantsProviders;

    /**
     * @param DataProcessorInterface[] $variantsProviders
     */
    public function __construct(
        array $variantsProviders = []
    ) {
        $this->variantsProviders = $variantsProviders;
    }

    /**
     * {@inheritdoc}
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
        $info = null
    ): void {
        foreach ($this->variantsProviders as $provider) {
            $provider->execute($arguments, $dataProcessorCallback, $metadata, $node, $info);
        }
    }

    /**
     * For backward compatibility with existing 3-rd party plugins.
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
}
