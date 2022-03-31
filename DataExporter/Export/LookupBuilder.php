<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Export;

/**
 * Build lookup value based on "using" field
 * Fields specified in "using" field in et_schema used to build relation between data returned in provider and
 * parent entity to be able to assign current entity to parent entity.
 * If usinf field not set field with type "ID" will be used by default
 */
class LookupBuilder
{

    /**
     * @param array $field
     * @param array $item
     * @return string
     */
    public static function build(array $field, array $item): string
    {
        $index = [];
        if (isset($field['using'])) {
            foreach ($field['using'] as $key) {
                if (!isset($key['field'], $item[$key['field']])) {
                    $fieldName = $key['field'] ?? '';
                    throw new \InvalidArgumentException(\sprintf(
                        'Exporter error: no value in Data Provider for "%s" in "using" field: "%s", item: %s',
                        $fieldName,
                        \var_export($field, true),
                        \var_export($item, true)
                    ));
                }
                // cast to string: we don't care about type here
                $index[] = [$key['field'] => (string)$item[$key['field']]];
            }
        }
        return base64_encode(json_encode($index));
    }
}
