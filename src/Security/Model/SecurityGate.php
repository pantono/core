<?php

namespace Pantono\Core\Security\Model;

class SecurityGate
{
    private string $name;
    private string $class;
    private array $services;
    private array $fields;
    private array $depends;
    private bool $global;

    public function __construct(string $name, string $class, array $services = [], array $fields = [], array $depends = [], bool $global = false)
    {
        $this->name = $name;
        $this->class = $class;
        $this->services = $services;
        $this->fields = $fields;
        $this->depends = $depends;
        $this->global = $global;
    }

    public static function fromArray(string $name, array $options): self
    {
        return new self($name, $options['class'], $options['services'] ?? [], $options['fields'] ?? [], $options['depends'] ?? [], $options['global'] ?? false);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getServices(): array
    {
        return $this->services;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getDepends(): array
    {
        return $this->depends;
    }

    public function isGlobal(): bool
    {
        return $this->global;
    }
}
