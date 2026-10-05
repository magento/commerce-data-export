<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\CatalogProductNumericAttribute\Plugin\Catalog\Model\ResourceModel\Eav;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

/**
 * Allows "numeric" frontend_input attributes to be used in catalog price rule conditions.
 *
 * The core ALLOWED_INPUT_TYPES private constant does not include "numeric".
 * This plugin extends isAllowedForRuleCondition() to accept it.
 */
class AttributePlugin
{
    private const FRONTEND_INPUT = 'numeric';

    /**
     * Allow numeric attributes for rule conditions.
     *
     * @param Attribute $subject
     * @param bool $result
     * @return bool
     */
    public function afterIsAllowedForRuleCondition(Attribute $subject, bool $result): bool
    {
        if ($result === false
            && $subject->getIsVisible()
            && $subject->getFrontendInput() === self::FRONTEND_INPUT
        ) {
            return true;
        }
        return $result;
    }
}
