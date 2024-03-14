<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Query;

use Magento\Framework\DB\Select;
use Magento\Framework\Mview\ViewInterface;

/**
 * Interface to get change log select
 */
interface ChangelogSelectQueryInterface
{
    /**
     * @param string $sourceTableName
     * @param string $sourceTableField
     * @param int $lastVersionId
     * @return Select
     */
    public function getChangelogSelect(
        string $sourceTableName,
        string $sourceTableField,
        int $lastVersionId
    ): Select;
}
