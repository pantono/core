<?php

namespace Pantono\Core\Validator\Model;

class ValidationResultField
{
    private string $name;
    private mixed $input = null;
    private mixed $value;
    private ?string $error = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getInput(): mixed
    {
        return $this->input;
    }

    public function setInput(mixed $input): void
    {
        $this->input = $input;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): void
    {
        $this->error = $error;
    }
}
