<?php

namespace Pantono\Core\CommandLine\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nette\PhpGenerator\PhpNamespace;
use Pantono\Utilities\ApplicationHelper;

class GenerateUnitTestCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('test:generate')
            ->addArgument('class', InputArgument::REQUIRED)
            ->addArgument('target_directory', InputArgument::OPTIONAL);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $class = $input->getArgument('class');
        if (!class_exists($class)) {
            $output->writeLn('<error>Class ' . $class . ' does not exist</error>');
        }
        $reflection = new \ReflectionClass($class);
        $con = $reflection->getConstructor();
        $target = null;
        if ($input->hasOption('target_directory')) {
            $target = $input->getOption('target_directory');
        }
        $namespaceString = $this->getTestNamespace($class);
        $parts = explode('\\', $namespaceString);
        $className = array_pop($parts) . 'Test';
        $namespaceString = implode('\\', $parts);
        $namespace = new PhpNamespace($namespaceString);
        $class = $namespace->addClass($className);
        $namespace->addUse(TestCase::class);
        $namespace->addUse(MockObject::class);
        $class->setExtends(TestCase::class);
        $setupBodyParts = [];
        $params = [];
        if ($con) {
            foreach ($con->getParameters() as $parameter) {
                if ($parameter->getType() instanceof \ReflectionNamedType) {
                    if ($parameter->getType()->isBuiltin() === false) {
                        $namespace->addUse($parameter->getType()->getName());
                        $class->addProperty($parameter->getName())->setType(MockObject::class . '|' . $parameter->getType()->getName());
                        $mockName = $parameter->getType()->getName();
                        if (str_contains($mockName, '\\') === true) {
                            $parts = explode('\\', $mockName);
                            $mockName = array_pop($parts);
                        }
                        $setupBodyParts[] = '$this->' . $parameter->getName() . ' = $this->getMockBuilder(' . $mockName . '::class)->disableOriginalConstructor()->getMock();';
                        $params[$parameter->getName()] = $parameter->getType();
                    }
                }
            }
        }
        $class->addMethod('setUp')->setReturnType('void')->setBody(implode(PHP_EOL, $setupBodyParts));
        if (!empty($params)) {
            $namespace->addUse($reflection->getName());
            $classCreate = $class->addMethod('createClass')->setReturnType($reflection->getName());
            $createConstructorParams = [];
            foreach ($params as $name => $type) {
                $createConstructorParams[] = '$this->' . $name;
            }
            $classCreate->setBody('return new \\' . $reflection->getName() . '(' . implode(',', $createConstructorParams) . ');');
        }
        if ($target === null) {
            $target = $this->getFilenameForClassName($namespaceString);
        }
        $this->checkDirectory($target);
        file_put_contents($target, '<?php' . PHP_EOL . $namespace);
        return 0;
    }

    private function getTestNamespace(string $fullyQualifiedClassName): string
    {
        $autoload = $this->getComposerAutoload();
        if (sizeof($autoload) === 1) {
            $keys = array_keys($autoload);
            $namespace = $keys[0];
            if (str_starts_with($fullyQualifiedClassName, $namespace)) {
                $fullyQualifiedClassName = str_replace($namespace, '', $fullyQualifiedClassName);
                return $namespace . 'Tests\\' . $fullyQualifiedClassName;
            }
        }
        $parts = explode('\\', $fullyQualifiedClassName);
        array_pop($parts);
        $first = array_shift($parts);
        return $first . '\Tests\\' . implode('\\', $parts);
    }

    private function getComposerAutoload(): array
    {
        $composerFile = ApplicationHelper::getApplicationRoot() . '/composer.json';
        if (file_exists($composerFile)) {
            $data = file_get_contents($composerFile);
            if ($data) {
                $data = json_decode($data, true);
                return $data['autoload']['psr-4'] ?? [];
            }
        }
        return [];
    }

    private function getFilenameForClassName(string $className): string
    {
        $autoload = $this->getComposerAutoload();
        if (empty($autoload)) {
            throw new \RuntimeException('Cannot ascertain composer autoload');
        }
        $keys = array_keys($autoload);
        $namespace = $keys[0];
        $dir = $autoload[$keys[0]];
        if (str_starts_with($className, $namespace)) {
            $className = substr($className, strlen($namespace));
            $className = $dir . str_replace('\\', '/', $className) . 'Test.php';
        } else {
            throw new \RuntimeException('Invalid namespace');
        }
        return ApplicationHelper::getApplicationRoot() . $className;
    }

    private function checkDirectory(string $filename): void
    {
        if (file_exists($filename)) {
            throw new \RuntimeException('Target test already exists');
        }
        $pathInfo = pathinfo($filename);
        if (isset($pathInfo['dirname'])) {
            if (!file_exists($pathInfo['dirname'])) {
                mkdir($pathInfo['dirname'], 0777, true);
            }
        }
    }
}
