<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesOrdersDataExporter\Console\Command;

use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\DataExporter\Uuid\UuidManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Console\Cli;
use Magento\Framework\DB\Query\BatchIteratorFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command provides possibility to full export system configuration
 */
class Link extends \Symfony\Component\Console\Command\Command
{
    private const COMMAND_NAME = 'commerce-data-export:orders:link';
    private const OPTION_STATE = 'state';
    private const OPTION_FROM = 'from';
    private const OPTION_TO = 'to';
    private const OPTION_BATCH_SIZE = 'batch-size';

    private $uuidManager;
    private $resourceConnection;
    private $batchIteratorFactory;

    private $mapTypes = [
        'order' => [
            'table' => 'sales_order',
            'id_column' => 'entity_id',
            'link_column' => 'entity_id'
        ],
        'order_item' => [
            'table' => 'sales_order_item',
            'id_column' => 'item_id',
            'link_column' => 'order_id'
        ],
        'credit_memo' => [
            'table' => 'sales_creditmemo',
            'id_column' => 'entity_id',
            'link_column' => 'order_id'
        ],
        'order_shipment' => [
            'table' => 'sales_shipment',
            'id_column' => 'entity_id',
            'link_column' => 'order_id'
        ]
    ];

    /**
     * @var CommerceDataExportLoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param UuidManager $uuidManager
     * @param BatchIteratorFactory $batchIteratorFactory
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        UuidManager $uuidManager,
        BatchIteratorFactory $batchIteratorFactory,
        CommerceDataExportLoggerInterface $logger
    ) {
        $this->uuidManager = $uuidManager;
        $this->resourceConnection = $resourceConnection;
        $this->batchIteratorFactory = $batchIteratorFactory;
        $this->logger = $logger;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->addOption(
            self::OPTION_STATE,
            's',
            InputOption::VALUE_OPTIONAL,
            'Statuses for filter order',
            ""
        );
        $this->addOption(
            self::OPTION_FROM,
            'f',
            InputOption::VALUE_OPTIONAL,
            'Date from for filter order',
            ""
        );
        $this->addOption(
            self::OPTION_TO,
            't',
            InputOption::VALUE_OPTIONAL,
            'Date to for filter order',
            ""
        );
        $this->addOption(
            self::OPTION_BATCH_SIZE,
            'b',
            InputOption::VALUE_OPTIONAL,
            'Batch size',
            10000
        );
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Assign UUID to orders to allow further export via "saas:resync" command');

        parent::configure();
    }

    /**
     * Updating UUID
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $state = $input->getOption(self::OPTION_STATE);
            $batchSize = (int) $input->getOption(self::OPTION_BATCH_SIZE);
            $from = $input->getOption(self::OPTION_FROM)
                ? (new \DateTime($input->getOption(self::OPTION_FROM)))->format(\DateTimeInterface::W3C)
                : null;
            $to = $input->getOption(self::OPTION_TO)
                ? (new \DateTime($input->getOption(self::OPTION_TO)))->format(\DateTimeInterface::W3C)
                : null;

            return $this->link($batchSize, $output, $from, $to, $state);
        } catch (\Throwable $e) {
            $output->writeln('<error>Failed to update UUID. Check logs</error>');
            $this->logger->error(
                sprintf('Command "%s" failed. Error message: %s', self::COMMAND_NAME, $e->getMessage())
            );

            return Cli::RETURN_FAILURE;
        }
    }

    /**
     * Updating UUID
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    public function prepareToExport(
        int $batchSize,
        OutputInterface $output,
        string $from = null,
        string $to = null,
        string $state = ''
    ) {
        try {
            $output->writeln(
                sprintf(
                    '<info>Start updating UUID with parameters [state=%s, from=%s, to=%s, batch_size=%s]</info>',
                    $state ?: 'all',
                    $from ?: date(\DateTimeInterface::W3C, 0),
                    $to ?: date(\DateTimeInterface::W3C),
                    $batchSize
                )
            );

            $updatedEntities = 0;
            foreach ($this->getOrders($state, $batchSize, $from, $to) as $type => $entityIds) {
                $this->uuidManager->assignBulk($entityIds, $type);
                $updatedEntities += count($entityIds);
            }
            $output->writeln(
                sprintf(
                    '<info>Update completed successfully, %s entities updated</info>',
                    $updatedEntities
                )
            );
            return Cli::RETURN_SUCCESS;

        } catch (\Throwable $e) {
            $output->writeln('<error>Failed to update UUID. Check logs</error>');
            $this->logger->error(
                sprintf('Command "%s" failed. Error message: %s', self::COMMAND_NAME, $e->getMessage())
            );

            return Cli::RETURN_FAILURE;
        }
    }

    /**
     * @param string $state
     * @param int $batchSize
     * @param string|null $from
     * @param string|null $to
     * @return \Generator
     */
    private function getOrders(string $state, int $batchSize, string $from = null, string $to = null): \Generator
    {

        $mapTypes = array_map(function ($type) {
            $type['table'] = $this->resourceConnection->getTableName($type['table']);
            return $type;
        }, $this->mapTypes);

        $connection = $this->resourceConnection->getConnection();
        $uuidTableName = $this->resourceConnection->getTableName('data_exporter_uuid');
        $orderTableName = $mapTypes['order']['table'];

        foreach ($mapTypes as $type => $data) {
            $select = $connection->select()
                ->from(
                    ['order' => $orderTableName],
                    []
                )
                ->joinInner(
                    ['child' => $data['table']],
                    "order.entity_id = child.{$data['link_column']}",
                    "child.{$data['id_column']} AS entity_id"
                )
                ->joinLeft(
                    ['uuid' => $uuidTableName],
                    "child.{$data['id_column']} = uuid.entity_id and uuid.type = '{$type}'",
                    []
                )
                ->where('uuid.uuid IS NULL');

            if (!empty($state)) {
                $select->where('order.state = ?', $state);
            }
            if (!empty($from)) {
                $select->where('order.created_at >= ?', $from);
            }
            if (!empty($to)) {
                $select->where('order.created_at <= ?', $to);
            }

            $iterator = $this->batchIteratorFactory->create(
                [
                    'select' => $select,
                    'batchSize' => $batchSize,
                    'correlationName' => 'child',
                    'rangeField' => $data['id_column'],
                    'rangeFieldAlias' => 'entity_id'
                ]
            );

            foreach ($iterator as $batchSelect) {
                yield $type => $connection->fetchCol($batchSelect);
            }
        }
    }
}
