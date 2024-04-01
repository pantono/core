<?php

namespace Pantono\Core\Validator\Validator;

use Symfony\Component\HttpFoundation\ParameterBag;
use Pantono\Contracts\Validation\ValidatorInterface;

abstract class ValidatorAbstract implements ValidatorInterface
{
    private ParameterBag $securityContext;
    private ParameterBag $parameterBag;
    private ParameterBag $requestParameters;
    private array $options = [];

    public function getSecurityContext(): ParameterBag
    {
        return $this->securityContext;
    }

    public function setSecurityContext(ParameterBag $securityContext): void
    {
        $this->securityContext = $securityContext;
    }

    public function getParameterBag(): ParameterBag
    {
        return $this->parameterBag;
    }

    public function setParameterBag(ParameterBag $parameterBag): void
    {
        $this->parameterBag = $parameterBag;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function getRequestParameters(): ParameterBag
    {
        return $this->requestParameters;
    }

    public function setRequestParameters(ParameterBag $parameters): void
    {
        $this->requestParameters = $parameters;
    }

    public function getOption(string $name, array $options = []): ?string
    {
        if (isset($options[$name])) {
            return $options[$name];
        }
        if (isset($this->options[$name])) {
            return $this->options[$name]['default'];
        }
        return null;
    }

    abstract public function isValid(mixed $input, array $options = []): void;
}
