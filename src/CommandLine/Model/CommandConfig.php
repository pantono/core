<?php

namespace Pantono\Core\CommandLine\Model;

class CommandConfig
{
    private string $name;
    private string $class;
    private array $services;

    public function __construct(string $name, string $class, array $services = [])
    {
        $this->name = $name;
        $this->class = $class;
        $this->services = $services;
    }

    public static function fromArray(string $name, array $config = []): self
    {
        return new self($name, $config['class'], $config['services'] ?? []);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getServices(): array
    {
        return $this->services;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getNamespace(): string
    {
        $parts = explode('\\', $this->getClass());
        array_pop($parts);
        return implode('\\', $parts);
    }

    public function getClassName(): string
    {
        $parts = explode('\\', $this->getClass());
        return array_pop($parts);
    }
}
