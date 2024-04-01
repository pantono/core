<?php

namespace Pantono\Core\Validator\Model;

use Symfony\Component\HttpFoundation\ParameterBag;

class ValidationResult
{
    /**
     * @var ValidationResultField[]
     */
    private array $fields = [];

    public function addField(ValidationResultField $field): void
    {
        $this->fields[] = $field;
    }

    public function isOk(): bool
    {
        foreach ($this->fields as $field) {
            if ($field->getError() !== null) {
                return false;
            }
        }
        return true;
    }

    public function hasField(string $name): bool
    {
        foreach ($this->fields as $field) {
            if ($field->getName() === $name) {
                return true;
            }
        }
        return false;
    }

    public function getFieldErrors(): array
    {
        $errors = [];
        foreach ($this->fields as $field) {
            if ($field->getError()) {
                $errors[] = [
                    'field' => $field->getName(),
                    'error' => $field->getError()
                ];
            }
        }
        return $errors;
    }

    public function getProcessedFieldInput(): ParameterBag
    {
        $fields = [];
        foreach ($this->fields as $field) {
            $fields[$field->getName()] = $field->getValue();
        }
        return new ParameterBag($fields);
    }
}
