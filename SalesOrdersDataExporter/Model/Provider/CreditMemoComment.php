<?php
/**
 * Copyright 2023 Adobe
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

class CreditMemoComment
{
    /**
     * Getting credit memo comments.
     *
     * @param array $values
     * @return array
     */
    public function get(array $values): array
    {
        $output = [];
        foreach ($values as $group) {
            foreach ($group as $creditMemo) {
                $output[] = [
                  'entityId' => $creditMemo['entityId'],
                  'creditMemoComments' => $creditMemo['comment'],
                ];
            }
        }
        return $output;
    }
}
