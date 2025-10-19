<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
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
