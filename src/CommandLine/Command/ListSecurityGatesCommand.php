<?php

namespace Pantono\Core\CommandLine\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Helper\Table;
use Pantono\Core\Router\Model\EndpointCollection;
use Pantono\Core\Security\Collection\SecurityGateCollection;

class ListSecurityGatesCommand extends Command
{
    private SecurityGateCollection $collection;

    public function __construct(SecurityGateCollection $collection)
    {
        $this->collection = $collection;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('security-gates:list');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $rows = [];
        $table = new Table($output);
        $table->setHeaders(['Name', 'Class', 'Global', 'Depends']);
        foreach ($this->collection->getAllGates() as $gate) {
            $rows[] = [
                $gate->getName(),
                $gate->getClass(),
                $gate->isGlobal() ? 'Yes' : 'No',
                implode(', ', $gate->getDepends())
            ];
        }
        $table->setRows($rows);
        $table->render();
        return 0;
    }
}
