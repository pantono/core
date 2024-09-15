<?php

namespace Pantono\Core\CommandLine\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Helper\Table;
use Pantono\Core\Router\Model\EndpointCollection;

class ListEndpointsCommand extends Command
{
    private EndpointCollection $collection;

    public function __construct(EndpointCollection $collection)
    {
        $this->collection = $collection;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('endpoint:list');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $rows = [];
        $table = new Table($output);
        $table->setHeaders(['Name', 'Method', 'Route', 'Controller', 'Security Gates']);
        foreach ($this->collection->getAllEndpoints() as $endpoint) {
            $exists = class_exists($endpoint->getController());
            $rows[] = [
                $endpoint->getId(),
                $endpoint->getMethod(),
                $endpoint->getRoute(),
                $endpoint->getController() . (!$exists ? ' ***' : ''),
                implode(', ', $endpoint->getSecurityGates())
            ];
        }
        $table->setRows($rows);
        $table->render();
        return 0;
    }
}
