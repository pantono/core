<?php

namespace Pantono\Core\CommandLine\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Helper\Table;
use Pantono\Core\Router\Model\EndpointCollection;
use Pantono\Contracts\Endpoint\EndpointDefinitionInterface;

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
                $this->getSecurityGateList($endpoint)
            ];
        }
        $table->setRows($rows);
        $table->render();
        return 0;
    }

    /**
     * @return array<int,string>
     */
    private function getSecurityGateList(EndpointDefinitionInterface $endpoint): array
    {
        $items = [];
        foreach ($endpoint->getSecurityGates() as $item) {
            $key = $item;
            if (is_array($item)) {
                $key = json_encode($item);
            }
            $items[] = $key;
        }
        return $items;
    }
}
