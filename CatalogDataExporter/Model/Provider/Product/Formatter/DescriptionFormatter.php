<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product\Formatter;

use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Catalog\Helper\Data;
use Magento\Framework\App\State;

/**
 * Parse tags for description field
 */
class DescriptionFormatter implements FormatterInterface
{
    private array $attributes;
    private Data $catalogHelper;
    private State $state;

    /**
     * @param Data $catalogHelper
     * @param State $state
     * @param array $attributes
     */
    public function __construct(
        Data $catalogHelper,
        State $state,
        array $attributes = [
            'description',
            'shortDescription'
        ]
    ) {
        $this->attributes = $attributes;
        $this->catalogHelper = $catalogHelper;
        $this->state = $state;
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function format(array $row): array
    {
        foreach ($row as $attribute => &$value) {
            if (!empty($value) && \in_array($attribute, $this->attributes, true)) {
                $value = $this->state->emulateAreaCode(
                    FrontNameResolver::AREA_CODE,
                    function ($value) {
                        return $this->catalogHelper->getPageTemplateProcessor()->filter($value);
                    },
                    [$value]
                );
            }
        }

        return $row;
    }
}
