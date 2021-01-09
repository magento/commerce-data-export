<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Console\Command;

use Magento\CatalogPriceDataExporter\Model\Synchronize;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command for exporting/synchronising price data
 */
class SynchronizePrices extends Command
{
    const INPUT_PRICE_TYPES = 'Price types';

    /**
     * @var State
     */
    protected $state;

    /**
     * @var Synchronize
     */
    private $sync;

    /**
     * @param Synchronize $sync
     * @param State $state
     */
    public function __construct(
        Synchronize $sync,
        State $state
    ) {
        $this->state = $state;
        $this->sync = $sync;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->addArgument(
            self::INPUT_PRICE_TYPES,
            InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
            'Optional list of price types to synchronize.',
            []
        );
        $this->setName('export:sync:prices')
            ->setDescription('Run full price export synchronisation');
        parent::configure();
    }

    /**
     * Run full price synchronisation
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $requestedTypes = $input->getArgument(self::INPUT_PRICE_TYPES);
        $returnValue = Cli::RETURN_SUCCESS;
        try {
            $output->writeln('Full price synchronisation started');
            $startTime = microtime(true);
            $this->sync->execute($requestedTypes);
            $resultTime = microtime(true) - $startTime;
            $output->writeln(
                __('Full price synchronisation finished in %time', ['time' => gmdate('H:i:s', (int)$resultTime)])
            );
        } catch (\Throwable $e) {
            $output->writeln('Full price synchronisation failed.');
            $output->writeln($e->getMessage());
            $output->writeln($e->getTraceAsString(), OutputInterface::VERBOSITY_DEBUG);
            $returnValue = Cli::RETURN_FAILURE;
        }
        return $returnValue;
    }
}
