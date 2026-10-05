<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\CatalogProductNumericAttribute\Plugin\Eav\Model\Entity;

use Magento\Eav\Model\Entity\Attribute;

/**
 * Maps frontend_input "numeric" to backend_type "decimal" and default value field "default_value_text".
 *
 * Core getBackendTypeByInput() and getDefaultValueByInput() have no case for "numeric";
 * without this plugin the backend type would be null and the attribute could not be saved.
 */
class AttributePlugin
{
    private const FRONTEND_INPUT = 'numeric';
    private const BACKEND_TYPE = 'decimal';
    private const DEFAULT_VALUE_FIELD = 'default_value_text';

    /**
     * Return "decimal" as backend type for the numeric input type.
     *
     * @param Attribute $subject
     * @param string|null $result
     * @param string $type
     * @return string|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetBackendTypeByInput(Attribute $subject, ?string $result, string $type): ?string
    {
        if ($type === self::FRONTEND_INPUT) {
            return self::BACKEND_TYPE;
        }
        return $result;
    }

    /**
     * Return "default_value_text" as default value field for the numeric input type.
     *
     * @param Attribute $subject
     * @param string|null $result
     * @param string $type
     * @return string|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetDefaultValueByInput(Attribute $subject, ?string $result, string $type): ?string
    {
        if ($type === self::FRONTEND_INPUT) {
            return self::DEFAULT_VALUE_FIELD;
        }
        return $result;
    }
}
