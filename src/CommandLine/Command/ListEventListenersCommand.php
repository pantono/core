<?php

namespace Pantono\Core\CommandLine\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Pantono\Container\Service\Collection\ServiceCollection;
use Symfony\Component\Console\Helper\Table;
use Pantono\Core\Events\Model\EventListenerCollection;

class ListEventListenersCommand extends Command
{

    private EventListenerCollection $eventListenerCollection;

    public function __construct(EventListenerCollection $eventListenerCollection)
    {
        $this->eventListenerCollection = $eventListenerCollection;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('events:list');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $rows = [];
        $table = new Table($output);
        $table->setHeaders(['Name', 'Class', 'Events']);
        foreach ($this->eventListenerCollection->getListeners() as $listener) {
            $rows[] = [
                $listener->getName(),
                $listener->getClass(),
                json_encode($listener->getSubscribedEvents())
            ];
        }
        $table->setRows($rows);
        $table->render();
        return 0;
    }
}
