<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Console\Command;

use Magento\CatalogPriceDataExporter\Model\EventPool;
use Magento\CatalogPriceDataExporter\Model\EventPublisher;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\CatalogPriceDataExporter\Model\EventBuilder;

/**
 * CLI command for exporting/synchronising all price data
 */
class FullSync extends Command
{
    /**
     * @var State
     */
    protected $state;

    /**
     * @var EventPool
     */
    private $eventPool;

    /**
     * @var EventBuilder
     */
    private $eventBuilder;

    /**
     * @var EventPublisher
     */
    private $eventPublisher;

    /**
     * @param EventPool $eventPool
     * @param EventBuilder $eventBuilder
     * @param EventPublisher $eventPublisher
     * @param State $state
     */
    public function __construct(
        EventPool $eventPool,
        EventBuilder $eventBuilder,
        EventPublisher $eventPublisher,
        State $state
    ) {
        $this->state = $state;
        $this->eventPool = $eventPool;
        $this->eventBuilder = $eventBuilder;
        $this->eventPublisher = $eventPublisher;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
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
        $returnValue = Cli::RETURN_SUCCESS;
        try {
            $output->writeln('Full price synchronisation started');
            $startTime = microtime(true);
            foreach ($this->eventPool->getFullReindexResolvers() as $resolver) {
                foreach ($resolver->retrieve() as $eventData) {
                    $this->processEvents($eventData);
                }
            }
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

    /**
     * Process event data
     *
     * @param array $eventData
     *
     * @throws LocalizedException
     */
    private function processEvents(array $eventData): void
    {
        $events = $this->eventBuilder->build($eventData);
        $this->eventPublisher->publishEvents($events);
    }
}
