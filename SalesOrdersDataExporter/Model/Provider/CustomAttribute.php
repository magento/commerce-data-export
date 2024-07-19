<?php
/**
 * Copyright 2022 Adobe
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
