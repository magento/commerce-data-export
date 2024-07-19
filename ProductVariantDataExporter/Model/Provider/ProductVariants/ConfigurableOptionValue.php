<?php
/**
 * Copyright 2021 Adobe
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
