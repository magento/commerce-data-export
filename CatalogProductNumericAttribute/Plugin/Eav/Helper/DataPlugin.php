<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\CatalogProductNumericAttribute\Plugin\Eav\Helper;

use Magento\Eav\Helper\Data;

/**
 * Ensures "numeric" is in the allowed input types list for attribute validation
 * so product attributes with input type Numeric can be saved from Admin.
 */
class DataPlugin
{
    private const FRONTEND_INPUT = 'numeric';

    /**
     * Add "numeric" to the validator input types so attribute save accepts it.
     *
     * @param Data $subject
     * @param array|null $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetInputTypesValidatorData(Data $subject, $result): array
    {
        $types = is_array($result) ? $result : [];
        if (!isset($types[self::FRONTEND_INPUT])) {
            $types[self::FRONTEND_INPUT] = self::FRONTEND_INPUT;
        }
        return $types;
    }
}
