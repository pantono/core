<?php

namespace Pantono\Core\Validator\Collection;

class ValidatorCollection
{
    /**
     * @var ValidatorConfig[]
     */
    private array $validators = [];

    public function addValidator(ValidatorConfig $validator): void
    {
        $this->validators[$validator->getName()] = $validator;
    }

    public function getValidator(string $name): ?ValidatorConfig
    {
        return $this->validators[$name] ?? null;
    }
}
