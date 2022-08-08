<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\SalesOrdersDataExporter\Model\Provider;

use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\DataExporter\Uuid\UuidManager;
use Magento\DataExporter\Export\Request\Node;

/**
 * Build UUID type for entity [id => "UUID"]
 * Assign UUID in runtime if not exists
 */
class Uuid
{
    private const UUID_FIELD_NAME = 'uuid';

    /**
     * @var UuidManager
     */
    private $uuidManager;

    /**
     * @var string
     */
    private $uuidType;

    /**
     * @var CommerceDataExportLoggerInterface
     */
    private $logger;

    /**
     * @param UuidManager $uuidManager
     * @param CommerceDataExportLoggerInterface $logger
     * @param string $uuidType
     */
    public function __construct(
        UuidManager $uuidManager,
        CommerceDataExportLoggerInterface $logger,
        string $uuidType
    ) {
        $this->uuidManager = $uuidManager;
        $this->uuidType = $uuidType;
        $this->logger = $logger;
    }

    /**
     * @param array $values
     * @param Node $node
     * @return array
     */
    public function get(array $values, Node $node): array
    {
        $ids = [];
        $fieldName = $node->getField()['name'];
        $fieldParentLink = array_key_first($node->getField()['using']);
        $values = $this->flatten($values);

        foreach($values as $value) {
            if (empty($value[self::UUID_FIELD_NAME])) {
                $ids[] = (int) $value[$fieldParentLink];
            }
        }

        if ($ids) {
            $this->logger->warning(
                \sprintf(
                    'Sales Order Exporter: assign UUID in runtime for type: %s, ids: %s',
                    $this->uuidType, implode(',', $ids)
                ),
            );
            $assignedUuids = $this->uuidManager->assignBulk($ids, $this->uuidType);
            foreach($values as &$value) {
                $value[self::UUID_FIELD_NAME] = $assignedUuids[$value[$fieldParentLink]];
            }
            unset($value);
        }
        $output = [];
        foreach ($values as $value) {
            if (empty($value[self::UUID_FIELD_NAME])) {
                throw new \RuntimeException(sprintf(
                    'uuid field is empty for type: %s, value: %s',
                    $this->uuidType,
                    \var_export($value, true)
                ));
            }
            $uniqueKey = $value[$fieldParentLink];
            $output[$uniqueKey] = [
                $fieldName => ['id' => $value[self::UUID_FIELD_NAME]],
                $fieldParentLink => $uniqueKey
            ];
        }

        return $output;
    }

    private function flatten($values)
    {
        if (isset(current($values)[0])) {
            return array_merge([], ...array_values($values));
        }
        return $values;
    }
}
