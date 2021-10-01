<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Model\Provider\ProductVariants;

use Magento\ProductVariantDataExporter\Model\Provider\ConfigurableOptionValueUid;

/**
 * Create configurable product variant option value uid in base64 encode
 */
class ConfigurableOptionValue implements OptionValueInterface
{
    /**
     * @var ConfigurableOptionValueUid
     */
    private $optionValueUid;

    public function __construct(ConfigurableOptionValueUid $optionValueUid)
    {
        $this->optionValueUid = $optionValueUid;
    }

    /**
     * Returns uid based on parent id, option id and optionValue uid
     *
     * @param array $row
     * @return array
     */
    public function resolve(array $row): array
    {
        $optionValueUid = $this->optionValueUid->resolve(
            $row['attributeId'],
            $row['optionValueId']
        );
        return [
            "attributeCode" => $row['attributeCode'],
            "uid" => $optionValueUid,
            "label" => $row['optionLabel'],
            "valueIndex" => $row['optionValueId'],
        ];
    }
}
