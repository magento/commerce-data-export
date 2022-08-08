<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\SalesOrdersDataExporter\Model\Provider;

use Magento\DataExporter\Export\Request\Info;
use Magento\DataExporter\Export\Request\Node;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\QueryXml\Model\QueryProcessor;

/**
 * Class for getting sales orders.
 */
class Orders extends \Magento\DataExporter\Model\Provider\QueryDataProvider
{
    private const COMMAND_NAME = 'bin/magento commerce-data-export:orders:link';

    /**
     * @var CommerceDataExportLoggerInterface
     */
    private $logger;

    /**
     * @param CommerceDataExportLoggerInterface $logger
     * @param QueryProcessor $queryProcessor
     * @param string|null $queryName
     * @param array $queryArguments
     */
    public function __construct(
        CommerceDataExportLoggerInterface $logger,
        QueryProcessor $queryProcessor,
        string $queryName = null,
        array $queryArguments = []
    ) {
        parent::__construct($queryProcessor, $queryName, $queryArguments);
        $this->logger = $logger;
    }

    /**
     * Get data from query
     *
     * @param array $values
     * @param Node $node
     * @param Info $info
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function get(array $values, Node $node, Info $info): array
    {
        $output = parent::get($values, $node, $info);
        if (!$output) {
            $this->logger->info(
                'No sales orders exported. To be able export previously created orders run command: '
                . self::COMMAND_NAME
            );
        }
        return $output;
    }
}
