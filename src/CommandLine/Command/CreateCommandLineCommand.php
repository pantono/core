<?php

namespace Pantono\Core\CommandLine\Command;

use Symfony\Component\Console\Command\Command;
use Pantono\Core\CommandLine\Model\CommandCollection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Style\SymfonyStyle;
use Pantono\Utilities\ApplicationHelper;
use Nette\PhpGenerator\PhpNamespace;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;
use Pantono\Container\Service\Locator;
use Nette\PhpGenerator\PsrPrinter;

class CreateCommandLineCommand extends Command
{
    private CommandCollection $collection;
    private Locator $locator;

    public function __construct(CommandCollection $collection, Locator $locator)
    {
        $this->collection = $collection;
        parent::__construct();
        $this->locator = $locator;
    }

    protected function configure(): void
    {
        $this->setName('command:generate')
            ->addArgument('name', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');
        $command = $this->collection->getCommandByByName($name);
        if ($command === null) {
            throw new \RuntimeException('Command ' . $name . ' does not exist');
        }
        $endpointDirectory = $this->getDirectoryFromNamespace($command->getNamespace());
        if (!file_exists($endpointDirectory)) {
            throw new \RuntimeException('Directory ' . $endpointDirectory . ' does not exist. Create before continuing');
        }
        $targetFile = $endpointDirectory . '/' . $command->getClassName() . '.php';
        $namespace = new PhpNamespace($command->getNamespace());
        $namespace->addUse(Command::class);
        $namespace->addUse(InputInterface::class);
        $namespace->addUse(OutputInterface::class);
        $class = $namespace->addClass($command->getClassName());
        $class->setExtends(Command::class);

        $response = new Question('What is the name of this command? (e.g. bin/console <name>) ', '');
        $commandName = (new QuestionHelper())->ask($input, $output, $response);
        if (!$commandName) {
            return 1;
        }
        if (!empty($command->getServices())) {
            $constructor = $class->addMethod('__construct');
            $constructorBodyParts = [];
            foreach ($command->getServices() as $serviceName) {
                $service = $this->locator->getServiceByIdentifier($serviceName);
                if ($service) {
                    $paramName = lcfirst($service->getName());
                    $namespace->addUse($service->getClassName());
                    $constructor->addParameter($paramName)->setType($service->getClassName());
                    $class->addProperty($paramName)->setType($service->getClassName());
                    $constructorBodyParts[] = '$this->' . $paramName . ' = $' . $paramName . ';';
                }
            }
            $constructorBodyParts[] = 'parent::__construct();';
            $constructor->setBody(implode(PHP_EOL, $constructorBodyParts));
        }

        $configure = $class->addMethod('configure')->setVisibility('protected');
        $configure->setReturnType('void');
        $configure->setBody('$this->setName(\'' . $commandName . '\');');
        $process = $class->addMethod('execute')->setReturnType('int');
        $process->addParameter('input')->setType(InputInterface::class);
        $process->addParameter('output')->setType(OutputInterface::class);
        $process->setBody('return 0;');
        $printer = new PsrPrinter();
        file_put_contents($targetFile, '<?php' . PHP_EOL . $printer->printNamespace($namespace));
        $io->success('Command generated at ' . $targetFile);
        return 0;
    }

    private function getDirectoryFromNamespace(string $namespace): string
    {
        $composer = $this->getComposerConfig();
        $autoload = $composer['autoload']['psr-4'] ?? [];
        foreach ($autoload as $autoloadNamespace => $autoloadPath) {
            $namespace = str_replace($autoloadNamespace, $autoloadPath, $namespace);
        }
        $parts = explode('\\', $namespace);
        array_unshift($parts);
        return ApplicationHelper::getApplicationRoot() . implode('/', $parts);
    }

    private function getComposerConfig(): array
    {
        $file = ApplicationHelper::getApplicationRoot() . '/composer.json';
        if (file_exists($file)) {
            $data = file_get_contents($file);
            if ($data) {
                return json_decode($data, true, 512, JSON_THROW_ON_ERROR);
            }
        }
        return [];
    }
}
