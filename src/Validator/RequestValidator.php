<?php

namespace Pantono\Core\Validator;

use Pantono\Core\Router\Model\EndpointDefinition;
use Symfony\Component\HttpFoundation\Request;
use Pantono\Core\Validator\Collection\ValidatorCollection;
use Pantono\Core\Validator\Model\ValidationResult;
use Pantono\Core\Validator\Model\ValidationResultField;
use Pantono\Utilities\DateTimeParser;
use Pantono\Container\Traits\ContainerAware;
use Pantono\Core\Validator\Validator\ValidatorAbstract;
use Pantono\Core\Validator\Exception\ValidationException;
use ReflectionClass;

class RequestValidator
{
    use ContainerAware;

    private ValidatorCollection $collection;

    public function __construct(?ValidatorCollection $collection = null)
    {
        if (!$collection) {
            $collection = new ValidatorCollection();
        }
        $this->collection = $collection;
    }


    public function validateRequest(EndpointDefinition $endpoint, Request $request): ValidationResult
    {
        $validationResult = new ValidationResult();
        $method = strtolower($endpoint->getMethod());
        if ($method === 'get' || $method === 'delete' || $method === 'options' || $method === 'head' || $method === 'trace') {
            $params = $request->query;
        } else {
            $params = $request->request;
        }
        foreach ($endpoint->getFields() as $endpointField) {
            $inputValue = $params->get($endpointField->getName());
            $field = new ValidationResultField();
            $field->setName($endpointField->getName());
            if ($inputValue) {
                $field->setInput($inputValue);
            }
            if ($endpointField->isRequired() === true) {
                if (!$inputValue) {
                    $field->setError($endpointField->getLabel() . ' is required');
                }
            }
            foreach ($endpointField->getValidators() as $validatorName => $options) {
                try {
                    $validator = $this->buildValidator($validatorName);
                    if (!$validator) {
                        throw new ValidationException('Validator ' . $validatorName . ' does not exist');
                    }
                    $validator->isValid($field->getInput(), $options ?? []);
                } catch (ValidationException $e) {
                    $field->setError($e->getMessage());
                }
            }
            if ($endpointField->getCast()) {
                if ($inputValue) {
                    $value = $this->processCast($endpointField->getCast(), $field->getInput());
                    if (!$value) {
                        $field->setError($endpointField->getLabel() . ' is invalid or cannot be found');
                    } else {
                        $field->setValue($value);
                    }
                }
            } else {
                $field->setValue($field->getInput());
            }
            $validationResult->addField($field);
        }

        foreach ($params->all() as $field => $value) {
            if ($validationResult->hasField($field) === false) {
                $validationField = new ValidationResultField();
                $validationField->setName($field);
                $validationField->setValue($value);
                $validationField->setInput($value);
                $validationResult->addField($validationField);
            }
        }
        
        return $validationResult;
    }

    private function buildValidator(string $validatorName): ?ValidatorAbstract
    {
        $validator = $this->collection->getValidator($validatorName);
        if (!$validator) {
            throw new \Exception('Validator ' . $validatorName . ' does not exist');
        }
        $validatorClass = $this->getContainer()->getLocator()->getClassAutoWire($validator->getClass());
        $validatorClass->setOptions($validator->getOptions());
        return $validatorClass;
    }

    private function processCast(string $className, mixed $value): mixed
    {
        if (!class_exists($className)) {
            return null;
        }
        $ref = new ReflectionClass($className);
        if ($ref->isUserDefined() === true) {
            return $this->getContainer()->getLocator()->lookupRecord($className, $value);
        } else {
            if (substr($className, 0, 1) === '\\') {
                $className = substr($className, 1);
            }
            if (strtolower($className) === 'datetime' || strtolower($className) === 'datetimeinterface') {
                return DateTimeParser::parseDate($value);
            }
            if (strtolower($className) === 'datetimeimmutable') {
                return DateTimeParser::parseDateImmutable($value);
            }
        }
        return null;
    }
}
