<?php

namespace Pantono\Core\CommandLine\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Pantono\Container\Service\Collection\ServiceCollection;
use Symfony\Component\Console\Helper\Table;

class ListServicesCommand extends Command
{
    private ServiceCollection $collection;

    public function __construct(ServiceCollection $collection)
    {
        $this->collection = $collection;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('services:list');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $rows = [];
        $table = new Table($output);
        $table->setHeaders(['Name', 'Class', 'Aliases']);
        foreach ($this->collection->getAllServices() as $service) {
            $rows[] = [
                $service->getName(),
                $service->getClassName(),
                json_encode($service->getAliases())
            ];
        }
        $table->setRows($rows);
        $table->render();
        return 0;
    }
}