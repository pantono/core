<?php

namespace Pantono\Core\Validator\Collection;

class ValidatorConfig
{
    private string $name;
    private string $class;
    private array $services;
    private array $options;

    public function __construct(string $name, string $class, array $services, array $options = [])
    {
        $this->name = $name;
        $this->class = $class;
        $this->services = $services;
        $this->options = $options;
    }

    public static function fromArray(string $name, array $config): self
    {
        return new self($name, $config['class'], $config['services'] ?? [], $config['options'] ?? []);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getServices(): array
    {
        return $this->services;
    }
}
