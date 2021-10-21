<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Export;

/**
 * Build lookup value based in "using" field
 */
class LookupBuilder {

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
                        'DataExporter error: No value in Data Provider for "%s" specified in "using" expression: "%s"',
                        $fieldName,
                        \var_export($field, true)
                    ));
                }
                // cast to string: we don't care about type here
                $index[] = [$key['field'] => (string)$item[$key['field']]];
            }
        }
        return base64_encode(json_encode($index));
    }
}
