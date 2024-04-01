<?php

namespace Pantono\Core\Router\Model;

class EndpointField
{
    private string $name;
    private string $type;
    private array $validators;
    private ?string $label;
    private ?string $cast;
    private bool $required;

    public function __construct(string $name, string $type, array $validators = [], ?string $label = null, ?string $cast = null, bool $required = false)
    {
        $this->name = $name;
        $this->type = $type;
        $this->validators = $validators;
        $this->label = $label;
        $this->cast = $cast;
        $this->required = $required;
    }

    public static function fromArray(string $name, array $field): self
    {
        return new self($name, $field['type'] ?? null, $field['validators'] ?? [], $field['label'] ?? null, $field['cast'] ?? null, $field['required'] ?? false);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getValidators(): array
    {
        return $this->validators;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getCast(): ?string
    {
        return $this->cast;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }
}
