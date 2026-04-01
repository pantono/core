<?php

namespace Pantono\Core\Generator;

use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;
use Pantono\Utilities\Model\PantonoReflectionModel;
use League\Fractal\TransformerAbstract;

class DecoratorGenerator
{
    /**
     * @var class-string $modelName
     */
    private string $modelName;

    /**
     * @param class-string $modelName
     */
    public function __construct(string $modelName)
    {
        if (!class_exists($modelName)) {
            throw new \RuntimeException('Model class does not exist: ' . $modelName);
        }
        $this->modelName = $modelName;
    }

    public function generate(string $targetNamespace): string
    {
        $namespace = new PhpNamespace($targetNamespace);
        $this->generateClass($namespace);
        $printer = new PsrPrinter();
        return $printer->printNamespace($namespace);
    }

    public function write(string $directoryName, string $targetNamespace): bool
    {
        $path = sprintf('%s/%s.php', $directoryName, $this->getDecoratorName());
        file_put_contents($path, '<?php' . PHP_EOL . $this->generate($targetNamespace));

        return file_exists($path);
    }

    private function generateClass(PhpNamespace $namespace): void
    {
        $reflection = new PantonoReflectionModel($this->modelName);
        $parts = explode('\\', $this->modelName);
        $className = array_pop($parts);
        $decoratorName = $className . 'Decorator';
        $namespace->addUse(TransformerAbstract::class);
        $namespace->addUse($this->modelName);
        $class = $namespace->addClass($decoratorName);
        $class->setExtends(TransformerAbstract::class);
        $transformer = $class->addMethod('transform');
        $transformer->setReturnType('array');
        $variableName = lcfirst($className);
        $transformer->addParameter($variableName)->setType($this->modelName);
        $standardProps = [];
        foreach ($reflection->getProperties() as $property) {
            $getter = $property->getGetter() . '()';
            if ($property->isTypeBuiltIn()) {
                $standardProps[$property->getPropertyNameSnakeCase()] = $getter;
            } elseif ($property->isDateType()) {
                $standardProps[$property->getPropertyNameSnakeCase()] = $getter . ($property->isNullable() ? '?' : '') . '->format(\'Y-m-d H:i:s\')';
            }
            //}
        }
        $body = 'return [' . PHP_EOL;
        foreach ($standardProps as $key => $getter) {
            $body .= sprintf('%s\'%s\' => $%s->%s,' . PHP_EOL, "\t", $key, $variableName, $getter);
        }
        $body .= '];';
        $transformer->setBody($body);
    }

    private function getDecoratorName(): string
    {
        $parts = explode('\\', $this->modelName);
        $className = array_pop($parts);
        return $className . 'Decorator';
    }
}
