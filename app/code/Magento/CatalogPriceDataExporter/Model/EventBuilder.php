<?php

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model;

use Magento\Store\Api\Data\WebsiteInterface;

/**
 * Class responsible for building event data array
 */
class EventBuilder
{
    /**
     * Build event data.
     *
     * @param string $eventType
     * @param string $entityId
     * @param string $scopeCode
     * @param string|null $customerGroup
     * @param string|null $value
     * @param array $additionalData
     *
     * @return array
     */
    public function build(
        string $eventType,
        string $entityId,
        string $scopeCode,
        ?string $customerGroup,
        ?string $value,
        array $additionalData = []
    ): array {
        $output = [
            'meta' => [
                'type' => $eventType,
            ],
            'data' => [
                'id' => $entityId,
                'w' => $scopeCode === WebsiteInterface::ADMIN_CODE ? null : $scopeCode,
                'cg' => $customerGroup,
            ]
        ];

        if (null !== $value) {
            $output['data']['value'] = $value;
        }

        if (!empty($additionalData)) {
            $output = \array_merge_recursive($output, $additionalData);
        }

        return $output;
    }
}
