<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
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
