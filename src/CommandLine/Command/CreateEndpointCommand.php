<?php

namespace Pantono\Core\CommandLine\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Pantono\Core\Router\Model\EndpointCollection;
use Nette\PhpGenerator\PhpNamespace;
use Pantono\Core\Router\Endpoint\AbstractEndpoint;
use Pantono\Container\Service\Locator;
use Pantono\Utilities\StringUtilities;
use Symfony\Component\HttpFoundation\ParameterBag;
use Pantono\Utilities\ApplicationHelper;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Nette\PhpGenerator\PsrPrinter;
use Symfony\Component\Console\Style\SymfonyStyle;
use League\Fractal\Resource\ResourceAbstract;
use Symfony\Component\Console\Helper\QuestionHelper;
use Pantono\Core\Router\Model\EndpointDefinition;

class CreateEndpointCommand extends Command
{
    private EndpointCollection $collection;
    private Locator $locator;

    public function __construct(EndpointCollection $collection, Locator $locator)
    {
        $this->collection = $collection;
        $this->locator = $locator;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('endpoints:generate')
            ->addArgument('name', InputArgument::REQUIRED, 'Name (key) of the endpoint');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $key = $input->getArgument('name');
        /**
         * @var ?EndpointDefinition $endpoint
         */
        $endpoint = $this->collection->getEndpointByKey($key);
        if ($endpoint === null) {
            throw new \RuntimeException('Endpoint does not exist');
        }

        $endpointDirectory = $this->getDirectoryFromNamespace($endpoint->getNamespace());
        if (!file_exists($endpointDirectory)) {
            throw new \RuntimeException('Directory ' . $endpointDirectory . ' does not exist. Create before continuing');
        }
        $targetFile = $endpointDirectory . '/' . $endpoint->getControllerName() . '.php';
        $namespace = new PhpNamespace($endpoint->getNamespace());
        $namespace->addUse(AbstractEndpoint::class);
        $class = $namespace->addClass($endpoint->getControllerName());
        $class->setExtends(AbstractEndpoint::class);
        $currentMethodParts = [];
        if (file_exists($targetFile)) {
            $response = new ConfirmationQuestion('The class already exists do you want to overwrite? ', false);
            $questionResponse = (new QuestionHelper())->ask($input, $output, $response);
            if ($questionResponse === false) {
                return 1;
            }
            $currentMethodParts = $this->getCurrentMethodBody($endpoint->getController());
        }

        if (!empty($endpoint->getServices())) {
            $constructor = $class->addMethod('__construct');
            $constructorBodyParts = [];
            foreach ($endpoint->getServices() as $service) {
                $serviceConfig = $this->locator->getServiceByIdentifier($service);
                if ($serviceConfig) {
                    $namespace->addUse($serviceConfig->getClassName());
                    $varName = lcfirst(StringUtilities::camelCase($serviceConfig->getName()));
                    $class->addProperty($varName)->setType($serviceConfig->getClassName())->setVisibility('private');
                    $constructorBodyParts[] = '$this->' . $varName . ' = $' . $varName . ';';
                    $constructor->addParameter($varName)->setType($serviceConfig->getClassName());
                }
            }
            $constructor->setBody(implode(PHP_EOL, $constructorBodyParts));
        }
        $processMethod = $class->addMethod('processRequest');
        $namespace->addUse(ResourceAbstract::class);
        $processMethod->setReturnType(ResourceAbstract::class . '|array');
        $namespace->addUse(ParameterBag::class);
        $processMethod->addParameter('parameters')->setType(ParameterBag::class);
        $bodyLines = [];
        foreach ($endpoint->getFields() as $config) {
            if ($config->getCast()) {
                if (class_exists($config->getCast())) {
                    $namespace->addUse($config->getCast());
                    $bodyLines[] = '/* @var ' . $config->getCast() . ' $' . $config->getName() . ' */';
                }
            }
            $bodyLines[] = '$' . $config->getName() . ' = $parameters->get(\'' . $config->getName() . '\');';
        }
        $bodyLines = array_merge($bodyLines, $currentMethodParts);
        $processMethod->setBody(implode(PHP_EOL, $bodyLines));
        $printer = new PsrPrinter();
        file_put_contents($targetFile, '<?php' . PHP_EOL . $printer->printNamespace($namespace));
        $io->success('Endpoint output to ' . $targetFile);
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

    private function getCurrentMethodBody(string $className): array
    {
        $parts = $this->getMethodBody($className);
        foreach ($parts as $index => $part) {
            $part = trim($part);
            $getterMatches = preg_match('/\$\w+ = \$parameters->get\(\'\w+\'\);/', $part);
            $phpDocMatches = preg_match('/\/\*\s*@var\s+\\\?[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*\s+\$\w+\s*\*\//', $part);
            if ($getterMatches === 0 && $phpDocMatches === 0) {
                $parts[$index] = $part;
            } else {
                unset($parts[$index]);
            }
        }
        return $parts;
    }

    private function getMethodBody(string $className): array
    {
        if (!class_exists($className)) {
            return [];
        }
        $class = new \ReflectionClass($className);
        $method = $class->getMethod('processRequest');
        $start = $method->getStartLine() + 1;
        $end = $method->getEndLine() - 1;
        $filename = $class->getFileName();
        if ($filename === false) {
            return [];
        }
        if (file_exists($filename)) {
            $contents = file_get_contents($filename);
            if ($contents !== false) {
                $lines = explode(PHP_EOL, $contents);
                return array_slice($lines, $start, ($end - $start));
            }
        }
        return [];
    }
}