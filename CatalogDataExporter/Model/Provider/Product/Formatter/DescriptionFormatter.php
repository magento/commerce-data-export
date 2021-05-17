<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product\Formatter;

use Magento\Catalog\Helper\Data;

/**
 * Parse tags for description field
 */
class DescriptionFormatter implements FormatterInterface
{
    /**
     * @var array
     */
    private $attributes;

    /**
     * @var Data
     */
    private $catalogHelper;

    /**
     * @param Data $catalogHelper
     * @param array $attributes
     */
    public function __construct(
        Data $catalogHelper,
        array $attributes = [
            'description',
            'shortDescription'
        ]
    ) {
        $this->attributes = $attributes;
        $this->catalogHelper = $catalogHelper;
    }

    /**
     * @inheritdoc
     */
    public function format(array $row): array
    {
        foreach ($row as $attribute => &$value) {
            if (!empty($value) && \in_array($attribute, $this->attributes, true)) {
                $value = $this->catalogHelper->getPageTemplateProcessor()->filter($value);
            }
        }

        return $row;
    }
}
