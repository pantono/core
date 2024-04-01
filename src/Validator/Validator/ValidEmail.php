<?php

namespace Pantono\Core\Validator\Validator;

use Pantono\Contracts\Validation\ValidatorInterface;
use Pantono\Core\Validator\Exception\InvalidEmailException;

class ValidEmail extends ValidatorAbstract implements ValidatorInterface
{
    public function isValid(mixed $input, array $options = []): void
    {
        if (filter_var($input, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidEmailException($input . ' is not a valid e-mail address');
        }
    }
}