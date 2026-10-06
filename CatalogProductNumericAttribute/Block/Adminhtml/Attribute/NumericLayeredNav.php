<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\CatalogProductNumericAttribute\Block\Adminhtml\Attribute;

use Magento\Backend\Block\Template;
use Magento\Framework\Registry;

/**
 * Provides saved attribute filterable values to the numeric layered navigation JS template.
 *
 * @api
 */
class NumericLayeredNav extends Template
{
    /**
     * @var Registry
     */
    private Registry $registry;

    /**
     * @param Template\Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->registry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Get the saved is_filterable value from the attribute being edited.
     *
     * @return int
     */
    public function getIsFilterable(): int
    {
        $attribute = $this->registry->registry('entity_attribute');
        return $attribute ? (int) $attribute->getData('is_filterable') : 0;
    }

    /**
     * Get the saved is_filterable_in_search value from the attribute being edited.
     *
     * @return int
     */
    public function getIsFilterableInSearch(): int
    {
        $attribute = $this->registry->registry('entity_attribute');
        return $attribute ? (int) $attribute->getData('is_filterable_in_search') : 0;
    }
}
