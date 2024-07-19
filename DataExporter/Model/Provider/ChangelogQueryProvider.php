<?php
/**
 * Copyright 2024 Adobe
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

namespace Magento\DataExporter\Model\Provider;

use Magento\DataExporter\Model\Query\ChangelogSelectQueryInterface;

/**
 * Class to obtain ChangeLogSelectQueryInterface instance according to view identifier
 */
class ChangelogQueryProvider
{
    private const DEFAULT_VIEW = 'default_view';

    /**
     * @var ChangelogSelectQueryInterface[]
     */
    private array $changeLogQuery;
    public function __construct(array $changeLogQuery)
    {
        $this->changeLogQuery = $changeLogQuery;
    }

    public function getChangeLogSelectQuery(
        string $viewIdentifier = self::DEFAULT_VIEW
    ): ChangelogSelectQueryInterface {
        return $this->changeLogQuery[$viewIdentifier] ?? $this->changeLogQuery[self::DEFAULT_VIEW];
    }
}
