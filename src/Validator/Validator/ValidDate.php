<?php

namespace Pantono\Core\Validator\Validator;

use Pantono\Contracts\Validation\ValidatorInterface;
use Pantono\Core\Validator\Exception\InvalidDateException;
use Pantono\Utilities\DateTimeParser;

class ValidDate extends ValidatorAbstract implements ValidatorInterface
{
    public function isValid(mixed $input, array $options = []): void
    {
        if ($input === null) {
            return;
        }
        try {
            if ($input instanceof \DateTimeInterface) {
                return;
            }
            $dateTime = \DateTime::createFromFormat((string)$this->getOption('date_format', $options), (string)$input);
            if (isset($options['future']) && $options['future'] === true) {
                if ($dateTime <= new \DateTime) {
                    throw new InvalidDateException($input . ' must be in the future');
                }
            }
            if (isset($options['past']) && $options['past'] === true) {
                if ($dateTime >= new \DateTime) {
                    throw new InvalidDateException($input . ' must be in the past');
                }
            }
        } catch (\Exception $e) {
            throw new InvalidDateException('Date ' . $input . ' is not in the format ' . $this->getOption('date_format'));
        }

        if (empty($dateTime)) {
            throw new InvalidDateException('Date ' . $input . ' is invalid');
        }
    }
}
