<?php
/**
 * Copyright 2024 Adobe
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

namespace Magento\CatalogDataExporter\Model\Provider\Product\Formatter;

use Magento\Catalog\Helper\Data;
use Magento\Framework\App\State;
use Magento\Framework\View\DesignInterface;

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
                    DesignInterface::DEFAULT_AREA,
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
