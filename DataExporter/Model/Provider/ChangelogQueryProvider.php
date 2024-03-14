<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
