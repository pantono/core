<?php

namespace Pantono\Core\CommandLine\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Pantono\Contracts\Application\Cache\ApplicationCacheInterface;
use Pantono\Utilities\ApplicationHelper;

class ClearSystemCacheCommand extends Command
{

    private ApplicationCacheInterface $cache;

    public function __construct(ApplicationCacheInterface $cache)
    {
        parent::__construct();
        $this->cache = $cache;
    }

    protected function configure(): void
    {
        $this->setName('system-cache:clear');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->write('Clearing cache....');
        $this->cache->clear();
        $proxyDir = ApplicationHelper::getApplicationRoot() . '/cache/proxies';
        if (file_exists($proxyDir) && is_dir($proxyDir)) {
            rmdir($proxyDir);
            mkdir($proxyDir, 0777, true);
        }
        $output->writeln('Done');
        return 0;
    }
}
