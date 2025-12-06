<?php

namespace Pantono\Core\Helper;

use Pantono\Core\Router\Model\EndpointDefinition;

class EndpointConfig
{
    private string $name;
    private string $controller;
    private string $route;
    private string $method = 'GET';
    private array $securityGates = [];
    private array $fields = [];
    private array $dependencies = [];

    public function __construct(string $name)
    {

        $this->name = $name;
    }

    public function setController(string $controller): self
    {
        $this->controller = $controller;
        return $this;
    }


    public function setMethod(string $method): self
    {
        $this->method = $method;
        return $this;
    }

    public function setRoute(string $route): self
    {
        $this->route = $route;
        return $this;
    }


    public function addSecurityGate(string $name): self
    {
        $this->securityGates[] = $name;
        return $this;
    }

    public function addDependency(string $name): self
    {
        $this->dependencies[] = $name;
        return $this;
    }

    public function addField(string $type, string $name, ?string $label = null, bool $required = false): self
    {
        $this->fields[$name] = [
            'type' => $type,
            'required' => $required,
            'label' => $label ?: $name
        ];
        return $this;
    }

    public function setFields(array $fields): self
    {
        $this->fields = $fields;
        return $this;
    }

    public function toEndpointDefinition(): EndpointDefinition
    {
        return EndpointDefinition::fromConfigArray($this->name, $this->toArray());
    }

    public function toArray(): array
    {
        $data = [
            'method' => $this->method,
            'route' => $this->route
        ];
        if ($this->controller) {
            $data['controller'] = $this->controller;
        }
        if (!empty($this->dependencies)) {
            $data['dependencies'] = $this->dependencies;
        }
        if (!empty($this->securityGates)) {
            $data['security_gates'] = $this->securityGates;
        }
        if (!empty($this->fields)) {
            $data['fields'] = $this->fields;
        }

        return $data;
    }
}
