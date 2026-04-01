<?php

namespace Pantono\Core\CommandLine\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Pantono\Core\Generator\DecoratorGenerator;
use Pantono\Utilities\ApplicationHelper;

class GenerateDecoratorCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('decorator:generate')
            ->addArgument('model', InputArgument::REQUIRED, 'Model to generate decorator for')
            ->addArgument('target_directory', InputArgument::OPTIONAL, 'Target directory to generate decorator in', 'src/Decorator');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $model = $input->getArgument('model');
        $targetDirectory = $input->getArgument('target_directory');
        $namespace = $this->directoryToNamespace($targetDirectory);
        $generator = new DecoratorGenerator($model);
        if ($generator->write($targetDirectory, $namespace)) {
            $output->writeln('<info>Decorator generated successfully</info>');
            return 0;
        } else {
            $output->writeln('<error>Decorator generation failed</error>');
            return 1;
        }

    }

    private function directoryToNamespace(string $directory): string
    {
        $composerFile = ApplicationHelper::getApplicationRoot() . '/composer.json';
        if (!file_exists($composerFile)) {
            throw new \RuntimeException('Composer file not found');
        }
        $contents = file_get_contents($composerFile);
        if (!$contents) {
            throw new \RuntimeException('Could not read composer file');
        }
        $composeData = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        $autoload = $composeData['autoload']['psr-4'] ?? [];
        $directory = rtrim($directory, '/') . '/';
        foreach ($autoload as $namespace => $path) {
            $path = rtrim($path, '/') . '/';
            if (str_starts_with($directory, $path)) {
                $remainder = substr($directory, strlen($path));
                return rtrim($namespace . str_replace('/', '\\', $remainder), '\\');
            }
        }
        return rtrim(str_replace('/', '\\', trim($directory, '/')), '\\');
    }
}
