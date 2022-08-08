<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\SalesOrdersDataExporter\Model\Provider;

use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class for getting array of custom attributes from string.
 */
class CustomAttribute
{
    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var string
     */
    private $usingField;

    /**
     * @param Json $jsonSerializer
     * @param string $usingField
     */
    public function __construct(
        Json $jsonSerializer,
        string $usingField
    ) {
        $this->jsonSerializer = $jsonSerializer;
        $this->usingField = $usingField;
    }

    /**
     * Getting additional attributes data.
     *
     * @param array $values
     * @return array
     */
    public function get(array $values): array
    {
        $output = [];

        foreach ($this->flatten($values) as $row) {
            if (\is_string($row['additionalInformation'])) {
                foreach ($this->getArrayFromJson($row['additionalInformation']) as $additionalInformation) {
                    $output[] = [
                        'additionalInformation' => $additionalInformation,
                        $this->usingField => $row[$this->usingField],
                    ];
                }
            }
        }
        return $output;
    }

    /**
     * @param string $rowData
     * @return array
     */
    private function getArrayFromJson(string $rowData): array
    {
        $additionalInformation = [];
        $unserializedData = $this->jsonSerializer->unserialize($rowData) ?? [];
        foreach ($unserializedData as $name => $value) {
            $additionalInformation[] = [
                'name' => $name,
                'value' => \is_string($value) ? $value : $this->jsonSerializer->serialize($value)
            ];
        }

        return $additionalInformation;
    }

    /**
     * @param $values
     * @return array
     */
    private function flatten($values): array
    {
        if (isset(current($values)[0])) {
            return array_merge([], ...array_values($values));
        }
        return $values;
    }
}
